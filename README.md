# Index Fungorum as a Catalogue of Life Data Package (ColDP)

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.7211135.svg)](https://doi.org/10.5281/zenodo.7211135)

A version of [Index Fungorum](http://www.indexfungorum.org) with persistent identifiers (e.g., DOIs) for literature added. Original data harvested via Index Fungorum API. Persistent identifiers added based on my earlier work [indexfungorum-publications](https://github.com/rdmpage/indexfungorum-publications).

### Exports and releases

The data to add to ChecklistBank are held in the views `names_with_references` and `references` in the SQLIte database. These views, together with the `metadata.yml` file comprise a release. Releases are versioned by date.


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

The approach used here is to make the reference id the Wikidata id by default, otherwise base it on one of the other persistent identifiers. We use a trigger to update `referenceID` whenever a row is edited. 

```sql
-- DROP TRIGGER referenceID_updated;
CREATE TRIGGER referenceID_updated AFTER UPDATE ON names FOR EACH ROW
BEGIN

UPDATE names
SET
    referenceID = COALESCE(wikidata, IIF(doi, ('doi:' || doi), NULL), IIF(handle, ('hdl:' || handle), NULL), IIF(jstor, ('jstor:' || jstor), NULL), IIF(biostor, ('biostor:' || biostor), NULL), url, pdf)
WHERE id = old.id;

END;
```



