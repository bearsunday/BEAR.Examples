SELECT id, slug, name, description, parent_id
FROM categories
WHERE slug = :slug
LIMIT 1
