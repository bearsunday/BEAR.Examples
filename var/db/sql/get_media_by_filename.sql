SELECT id, filename, mime_type, url, alt, width, height FROM media WHERE filename = :filename LIMIT 1
