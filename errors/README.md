# Errors

If I discover errors or records otherwise needing attention in Index Fungorum I add a value to the field `rdmpcomment`. The following query generates a dump of these:

```sql
SELECT id, namecomplete, authorship, publishedin, identifier, title, volume, number, pages, year, doi, rdmpcomment FROM names WHERE rdmpcomment IS NOT NULL;
```

Lists of errors that have been sent to Index Fungorum (i.e., Paul Kirk) are in this directory.

