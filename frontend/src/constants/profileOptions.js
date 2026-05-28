const formatLabel = (value) => {
  if (!value) return ''
  return String(value)
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const toOptions = (values) => values.map((value) => ({ value, label: formatLabel(value) }))

const DATING_PURPOSE_VALUES = [
  'Serious Relationship',
  'Long-term Relationship',
  'Short-term Relationship',
  'Casual Friendship',
  'Open to Possibilities',
]

const BODY_TYPE_VALUES = ['slim', 'athletic', 'average', 'curvy', 'plus_size']

const HABIT_VALUES = ['never', 'sometimes', 'often']

const EYE_COLOR_VALUES = [
  'brown',
  'dark_brown',
  'blue',
  'dark_blue',
  'green',
  'gray',
  'gray_green',
  'gray_blue',
  'hazel',
  'amber',
  'black',
]

const HAIR_COLOR_VALUES = ['black', 'brown', 'blonde', 'red', 'gray', 'white', 'other']

const CHILDREN_VALUES = ['has', 'wants', 'doesnt_want', 'open']

const ZODIAC_VALUES = [
  'aries',
  'taurus',
  'gemini',
  'cancer',
  'leo',
  'virgo',
  'libra',
  'scorpio',
  'sagittarius',
  'capricorn',
  'aquarius',
  'pisces',
]

const INTEREST_VALUES = [
  'music',
  'guitar',
  'piano',
  'singing',
  'dj',
  'concerts',
  'movies',
  'series',
  'anime',
  'documentaries',
  'reading',
  'self_development',
  'psychology',
  'philosophy',
  'history',
  'gym',
  'yoga',
  'running',
  'cycling',
  'swimming',
  'martial_arts',
  'football',
  'basketball',
  'volleyball',
  'tennis',
  'skiing',
  'snowboarding',
  'climbing',
  'dancing',
  'boxing',
  'cooking',
  'baking',
  'coffee',
  'wine',
  'restaurants',
  'vegetarianism',
  'traveling',
  'backpacking',
  'camping',
  'road_trips',
  'video_games',
  'board_games',
  'poker',
  'chess',
  'drawing',
  'painting',
  'photography',
  'design',
  'writing',
  'blogging',
  'crafts',
  'dogs',
  'cats',
  'animals',
  'hiking',
  'fishing',
  'gardening',
  'surfing',
  'programming',
  'crypto',
  'startups',
  'ai',
  'meditation',
  'spirituality',
  'astrology',
  'theater',
  'comedy',
  'karaoke',
  'nightlife',
]

const SELECT_OPTIONS = {
  datingPurpose: toOptions(DATING_PURPOSE_VALUES),
  bodyType: toOptions(BODY_TYPE_VALUES),
  smoking: toOptions(HABIT_VALUES),
  drinking: toOptions(HABIT_VALUES),
  exercise: toOptions(HABIT_VALUES),
  eyeColor: toOptions(EYE_COLOR_VALUES),
  hairColor: toOptions(HAIR_COLOR_VALUES),
  children: toOptions(CHILDREN_VALUES),
  zodiacSign: toOptions(ZODIAC_VALUES),
}

const INTEREST_OPTIONS = toOptions(INTEREST_VALUES)

export { SELECT_OPTIONS, INTEREST_OPTIONS, formatLabel }
