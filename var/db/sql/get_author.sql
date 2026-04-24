SELECT
    id,
    name,
    email,
    bio
FROM authors
WHERE id = :id
LIMIT 1
