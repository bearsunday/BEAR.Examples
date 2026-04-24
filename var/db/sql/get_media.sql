SELECT
    id,
    filename,
    mime_type,
    url,
    alt,
    width,
    height
FROM media
WHERE id = :id
LIMIT 1
