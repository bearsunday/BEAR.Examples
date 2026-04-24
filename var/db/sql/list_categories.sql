SELECT
    id,
    slug,
    name,
    description,
    parent_id
FROM categories
ORDER BY parent_id IS NOT NULL, parent_id, id
