# Index Fungorum as a Catalogue of Life Data Package (ColDP)

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.7211135.svg)](https://doi.org/10.5281/zenodo.7211135)

A version of [Index Fungorum](http://www.indexfungorum.org) with persistent identifiers (e.g., DOIs) for literature added. Original data harvested via Index Fungorum API. Persistent identifiers added based on my earlier work [indexfungorum-publications](https://github.com/rdmpage/indexfungorum-publications).

### Exports and releases

The data to add to ChecklistBank are held in the views `names_with_references` and `references` in the SQLIte database. These views should be exported as `names.tsv` and `references.tsv` respectively (in tab-delimited format), and together with the `metadata.yml` file comprise a release. Releases are versioned by date, and automatically get assigned a DOI via Zenodo. 

Note that the release should only include ColDP files so anything else should not be in the release. Add any unwanted files to a file called `.gitattributes`:

```
/code export-ignore
/junk export-ignore

*.db export-ignore
*.bbprojectd export-ignore
*.md export-ignore

*.gitattributes export-ignore
*.gitignore export-ignore

if.db filter=lfs diff=lfs merge=lfs -text
```

### Data needed for ChecklistBank

We need to include a formatted citation for uploading to ChecklistBank, we try to get these form local databases, as a last resource try and get from Wikidata or content negotiation from DOI.

### Adding to ChecklistBank

For now this process is not automated, so we need to do this manually. Zip `names.tsv` and `references.tsv` together and upload to ChecklistBank. If you change `metadata.yml`, then upload that file directly to ChecklistBank.

### Triple store

Create triples using `export-triples.php` which generates triples creating taxon name and linking to publication. Can upload to local Oxigraph for testing and exploration. 

```
curl 'http://localhost:7878/store?graph=http://www.indexfungorum.org' -H 'Content-Type:application/n-triples' --data-binary '@triples.nt'  --progress-bar
```

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



