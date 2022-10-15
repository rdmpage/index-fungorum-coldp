# Index Fungorum as a Catalogue of Life Data Package (ColDP)

A version of [Index Fungorum](http://www.indexfungorum.org) with persistent identifiers (e.g., DOIs) for literature added. Original data harvested via Index Fungorum API. Persistent identifiers added based on my earlier work [indexfungorum-publications](https://github.com/rdmpage/indexfungorum-publications).

## Issues

### Triggers

Trigger to touch the `updated` value every time we edit a row.

```sql
CREATE TRIGGER names_updated AFTER UPDATE ON names FOR EACH ROW
BEGIN

UPDATE names
SET
    updated = CURRENT_TIMESTAMP
WHERE id = old.id;

END;
```

### Reference ids

The ColDP model assumes that we have reference-level identifiers, that is, `reference.ID` is a work-level reference. For datasets like Index Fungorum where references are typically microcitations (i.e., pointers to a specific page) this model doesn’t work. The creators of Index Fungorum have adopted the approach of using the integer id for the name as the reference id as well, thus ensuring that it is unique within Index Fungorum. The creators of IPNI use DOIs as reference ids when available. However, the `/` does not play nice with the ColDP interface (even when URI encoded in the dataset).

On approach is to create a reference id based on the external identifiers for a reference, or which there may be more than one. The SQLite `COALESCE` function would be useful here.

```sql
-- create a referenceID for all names with some sort of identifier
UPDATE names SET referenceID =
         COALESCE(	
        	wikidata,
        	IIF(doi, ('doi:' || doi), NULL),
          IIF(handle, ('hdl:' || handle), NULL),
        	IIF(jstor, ('jstor:' || jstor), NULL),
          IIF(biostor, ('biostor:' || biostor), NULL),
          url,
          pdf
    	)
 WHERE wikidata IS NOT NULL OR doi IS NOT NULL OR jstor IS NOT NULL OR biostor IS NOT NULL OR url IS NOT NULL OR pdf IS NOT NULL;
```

By default the reference id is the Wikidata item id.

We can also add a trigger to update `referenceID` whenever a row is edited. 

```sql
CREATE TRIGGER referenceID_updated AFTER UPDATE ON names FOR EACH ROW
BEGIN
UPDATE names SET referenceID =
	COALESCE(	
		wikidata,
		IIF(doi, ('doi:' || doi), NULL),
		IIF(handle, ('hdl:' || handle), NULL),
		IIF(jstor, ('jstor:' || jstor), NULL),
		IIF(biostor, ('biostor:' || biostor), NULL),
		url,
		pdf
	)
WHERE id = old.id;
END;
```



