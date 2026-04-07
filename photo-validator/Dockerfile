FROM golang:1.24

RUN apt-get update && apt-get install -y \
    pkg-config \
    libopencv-dev \
    build-essential \
    cmake \
    git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY go.mod go.sum ./
RUN go mod tidy
COPY . .

RUN go build -o server validator.go

EXPOSE 9000

CMD ["./server"]
