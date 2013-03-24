SELECT
	REPLACE(SUBSTRING_INDEX(REPLACE(REPLACE(url, 'https://', ''), 'http://', ''), '/', 1), 'www.', '') domain,
	COUNT(1) num
FROM
	l_urls
GROUP BY
	domain
ORDER BY
	num desc
