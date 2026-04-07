package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"gocv.io/x/gocv"
	"image"
	"io"
	"log"
	"mime/multipart"
	"net/http"
	"sync"
)

type FaceDetectionResponse struct {
	Result  bool   `json:"result"`
	Message string `json:"message"`
}

var (
	net      gocv.Net
	netMutex sync.Mutex
)

func init() {
	model := "models/res10_300x300_ssd_iter_140000.caffemodel"
	config := "models/deploy.prototxt"

	net = gocv.ReadNetFromCaffe(config, model)
	if net.Empty() {
		log.Fatal("Error loading DNN model")
	}
}

func detectFacesDNN(imgData []byte) (int, error) {
	img, err := gocv.IMDecode(imgData, gocv.IMReadColor)
	if err != nil {
		return 0, err
	}
	defer img.Close()

	blob := gocv.BlobFromImage(img, 1.0, image.Pt(300, 300), gocv.NewScalar(104, 177, 123, 0), false, false)
	defer blob.Close()

	netMutex.Lock()
	net.SetInput(blob, "")
	detections := net.Forward("")
	netMutex.Unlock()
	defer detections.Close()

	reshaped := detections.Reshape(1, detections.Total()/7)
	defer reshaped.Close()

	count := 0
	for i := 0; i < reshaped.Rows(); i++ {
		confidence := reshaped.GetFloatAt(i, 2)
		if confidence > 0.5 {
			count++
		}
	}

	return count, nil
}

func faceDetectionHandler(w http.ResponseWriter, r *http.Request) {
	err := r.ParseMultipartForm(20 << 20) // 20MB
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{
			"error": "Cannot parse form",
		})
		return
	}

	files := r.MultipartForm.File["user_photos"]
	if len(files) == 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{
			"error": "No files uploaded",
		})
		return
	}

	if len(files) > 6 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{
			"error": "Too many files uploaded, maximum allowed is 6",
		})
		return
	}

	for _, fh := range files {
		contentType := fh.Header.Get("Content-Type")
		if len(contentType) == 0 || contentType[:6] != "image/" {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{
				"error": fmt.Sprintf("File %s is not an image", fh.Filename),
			})
			return
		}
	}

	type result struct {
		filename string
		response FaceDetectionResponse
	}

	resultCh := make(chan result, len(files))

	for _, fileHeader := range files {
		go func(fh *multipart.FileHeader) {
			file, err := fh.Open()
			if err != nil {
				resultCh <- result{
					filename: fh.Filename,
					response: FaceDetectionResponse{Result: false, Message: "cannot open file"},
				}
				return
			}
			defer file.Close()

			buf := bytes.NewBuffer(nil)
			if _, err := io.Copy(buf, file); err != nil {
				resultCh <- result{
					filename: fh.Filename,
					response: FaceDetectionResponse{Result: false, Message: "cannot read file"},
				}
				return
			}

			count, err := detectFacesDNN(buf.Bytes())

			var res FaceDetectionResponse
			if err != nil {
				res = FaceDetectionResponse{
					Result:  false,
					Message: "error processing image",
				}
			} else {
				switch {
				case count == 1:
					res = FaceDetectionResponse{
						Result:  true,
						Message: "one face detected",
					}
				case count > 1:
					res = FaceDetectionResponse{
						Result:  false,
						Message: "multiple faces detected",
					}
				default:
					res = FaceDetectionResponse{
						Result:  false,
						Message: "no face detected",
					}
				}
			}

			resultCh <- result{filename: fh.Filename, response: res}
		}(fileHeader)
	}

	results := make(map[string]FaceDetectionResponse)
	for i := 0; i < len(files); i++ {
		r := <-resultCh
		results[r.filename] = r.response
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(results)
}

func main() {
	defer net.Close()

	http.HandleFunc("/validate-user-photos", faceDetectionHandler)
	fmt.Println("Server running at http://localhost:9000")
	log.Fatal(http.ListenAndServe(":9000", nil))
}
