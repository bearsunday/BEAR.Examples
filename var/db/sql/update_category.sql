UPDATE categories
SET name = :name,
    description = :description,
    parent_id = :parentId
WHERE id = :id
