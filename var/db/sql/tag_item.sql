SELECT
    id,
    slug,
    name
FROM tags
WHERE id = :id
LIMIT 1
