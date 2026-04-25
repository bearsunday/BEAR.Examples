# Fake data observations (semantic-ex Phase 2)

Generated from var/fake/*.json — 50 records per atomic entity.

## Article
- `id` (int): min=1 max=50 nulls=0/50
- `slug` (str): min_len=3 max_len=48 typical=32 nulls=0/50
- `title` (str): min_len=10 max_len=48 typical=32 nulls=0/50
- `body` (str): min_len=5 max_len=445 typical=340 nulls=0/50
- `excerpt` (str): min_len=5 max_len=140 typical=140 nulls=0/50
- `status` (str): min_len=5 max_len=9 typical=9 nulls=0/50
- `publishedAt` (str): min_len=20 max_len=20 typical=20 nulls=8/50
- `authorId` (int): min=1 max=50 nulls=0/50
- `categoryId` (int): min=1 max=50 nulls=0/50

## Category
- `id` (int): min=1 max=50 nulls=0/50
- `slug` (str): min_len=2 max_len=16 typical=9 nulls=0/50
- `name` (str): min_len=2 max_len=20 typical=9 nulls=0/50
- `description` (str): min_len=0 max_len=61 typical=31 nulls=7/50
- `parentId` (int): min=1 max=10 nulls=40/50

## Tag
- `id` (int): min=1 max=50 nulls=0/50
- `slug` (str): min_len=2 max_len=14 typical=7 nulls=0/50
- `name` (str): min_len=3 max_len=20 typical=7 nulls=0/50

## Author
- `id` (int): min=1 max=50 nulls=0/50
- `name` (str): min_len=5 max_len=18 typical=11 nulls=0/50
- `email` (str): min_len=20 max_len=32 typical=25 nulls=0/50
- `bio` (str): min_len=0 max_len=49 typical=36 nulls=0/50

## Media
- `id` (int): min=1 max=50 nulls=0/50
- `filename` (str): min_len=13 max_len=14 typical=13 nulls=0/50
- `mimeType` (str): min_len=9 max_len=13 typical=10 nulls=0/50
- `url` (str): min_len=20 max_len=21 typical=20 nulls=0/50
- `alt` (str): min_len=0 max_len=52 typical=31 nulls=3/50
- `width` (int): min=0 max=1920 nulls=0/50
- `height` (int): min=0 max=1200 nulls=0/50

