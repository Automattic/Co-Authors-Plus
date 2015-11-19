SELECT wp_posts.* 
FROM wp_posts
LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
LEFT JOIN wp_term_taxonomy ON ( wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id )
WHERE 1=1
  AND (
        (wp_posts.post_author = 2 OR
          (wp_term_taxonomy.taxonomy = 'author'  AND wp_term_taxonomy.term_id = '20940')  OR
          (wp_term_taxonomy.taxonomy = 'author' AND wp_term_taxonomy.term_id = '906')
        )
      )
  AND wp_posts.post_type = 'post'
      AND (
        wp_posts.post_status = 'publish'
        OR wp_posts.post_status = 'future'
        OR wp_posts.post_status = 'draft'
        OR wp_posts.post_status = 'pending'
        OR wp_posts.post_status = 'private'
      )
GROUP BY wp_posts.ID HAVING MAX(
                    IF ( wp_term_taxonomy.taxonomy = 'author',
                      IF (  wp_term_taxonomy.term_id = '20940' OR  wp_term_taxonomy.term_id = '906',2,1 )
                      ,0 )
                  ) <> 1
ORDER BY wp_posts.post_date DESC