
SELECT
	u.title,
	u.url,
	GROUP_CONCAT(t.tag SEPARATOR ' ') AS tags
FROM
	l_urls u, l_links l, l_tags t
WHERE
	u.id = l.url_id AND l.tag_id = t.id
GROUP BY
	u.id;
