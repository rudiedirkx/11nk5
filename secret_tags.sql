select
	t.*,
	(select count(1) from l_links where tag_id = t.id) links,
	(select count(1) from l_links l1 join l_links l2 on l2.url_id = l1.url_id where l1.tag_id = t.id) xlinks
from l_tags t
having links > 1 and links = xlinks
order by t.tag asc
