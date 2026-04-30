SELECT
    id,
    slug,
    name,
    description,
    parent_id
FROM categories
WHERE id = :id
LIMIT 1
