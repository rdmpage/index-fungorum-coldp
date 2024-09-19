# Index Fungorum as a Catalogue of Life Data Package (ColDP)

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.7211135.svg)](https://doi.org/10.5281/zenodo.7211135)

A version of [Index Fungorum](http://www.indexfungorum.org) with persistent identifiers (e.g., DOIs) for literature added. Original data harvested via Index Fungorum API. Persistent identifiers added based on my earlier work [indexfungorum-publications](https://github.com/rdmpage/indexfungorum-publications).

Errors found in the original Index Fungorum are saved in the `errors` directory.

## Fungal Names

Note that in addition to Index Fungorum and MycoBank there his also [Fungal Names](https://nmdc.cn/fungalnames/) which provides downloads of names and identifiers as tab-delimited text, see https://nmdc.cn/fungalnames/released.

These files seem a bit broken as records with spaces in the classification, or ampersands in authorship can be split across multiple lines :(. I’ve imported Fungal_names_all_v2023_09_11 into SQLite.

To find names we’ve missed (although some identifiers may no longer resolve as they’ve been replaced):

```
SELECT `Registration identifier` FROM fungalnames
LEFT JOIN names ON names.id = fungalnames.`Registration identifier`
WHERE id IS NULL ORDER BY CAST(`Registration identifier` AS INTEGER);
```


## Identifiers

Index Fungorum integer numbers may have different sources depending on what range they fall into, see [Identifier Allocations](https://www.indexfungorum.org/names/IndexFungorumIdentifierAllocation.htm).

| Range | Database | Notes |
|--|--|--|
|<100001 | | used for all entries in the Dictionary of the Fungi  but ‘adopted’ for supraspecific names in Index Fungorum |
|100001 – 489999 | Index Fungorum | |
|490000 – 499999 | MycoBank |used for missing names
|500000 – 520000 | MycoBank |used for registration
|520001 – 549999 | Index Fungorum |used for unregistered names and missing names|
|550000 – 559999 | Index Fungorum |used for registration|
|560000 – 566348 | MycoBank |used for registration and missing names|
|566349 – 569999 | Index Fungorum |used for missing names|
|570000 – 579999 | Fungal Names |used for registration|
|580000 – 589999 | Index Fungorum |used for missing names|
|590000 – 599999 | Index Fungorum |used for manual addition of typifications|
|600000 – 699999 | Index Fungorum |used for missing names|
|700000 – 799999 | Index Fungorum |used for infrageneric names|
|800000 – 899999 | MycoBank |used for registration and missing names|
|900000 – 999999 | Index Fungorum |used for registration|
|10000000 – 10999999 | MycoBank |used for registration of typifications|

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


