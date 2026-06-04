SELECT id, provider, subject, author_id, email, name
FROM auth_identities
WHERE provider = :provider AND subject = :subject
LIMIT 1
