# Naming Conventions

Some naming conventions that are used throughout the keeko tools, especially from code perspective, to avoid confusion.

## CLI Commands

```
keeko init
```

__Parameters__

| Name             | Shortcut | Usage                                                                              |
|------------------|----------|------------------------------------------------------------------------------------|
| --name           |          | Name of the package                                                                |
| --namespace      | -ns      | The package's namespace for the src/ folder (If ommited, the package name is used) |
| --type           | -t       | The type of the package (app|module)                                               |
| --description    | -d       | Description of the package                                                         |
| --author         |          | Author name of the package                                                         |
| --license        | -l       | License of the package                                                             |
| --title          |          | The package's title (If ommited, second part of the package name is used)          |
| --classname      | -c       | The main class name (If ommited, there is a default handler)                       |
| --slug           |          | The slug (if this package is a keeko-module, anyway it's ignored)                  |
| --default-action |          | The module's default action                                                        |
| --force          | -f       | Allows to overwrite existing values                                                |

---


```
keeko generate:action
```

__Parameters__

| Name        | Shortcut | Usage                                                                                                                         |
|-------------|----------|-------------------------------------------------------------------------------------------------------------------------------|
| --classname | -c       | The main class name (If ommited, class name will be guessed from action name)                                                 |
| --model     | -m       | The model for which the actions should be generated, when there is no name argument (if ommited all models will be generated) |
| --title     |          | The title for the generated action                                                                                            |
| --type      |          | The type of this action (list|create|read|update|delete) (if ommited template is guessed from action name)                    |
| --acl       |          | The acl's for this action (guest, user and/or admin) (multiple values allowed)                                                |
| --schema    | -s       | Path to the database schema (if ommited, database/schema.xml is used)                                                         |
| --composer  |          | Path to the composer.json (if ommited, composer.json from the current directory is used)                                      |

__Arguments__

| Name | Usage                                                                                                      |
|------|------------------------------------------------------------------------------------------------------------|
| name | The name of the action, which should be generated. Typically in the form %nomen%-%verb% (e.g. user-create) |

---

```
keeko generate:response
```

__Parameters__

| Name       | Shortcut | Usage                                                                                    |
|------------|----------|------------------------------------------------------------------------------------------|
| --format   |          | The response format to create (default: "json")                                          |
| --template |          | The template for the body method (blank or twig) (default: "blank")                      |
| --schema   | -s       | Path to the database schema (if ommited, database/schema.xml is used)                    |
| --composer |          | Path to the composer.json (if ommited, composer.json from the current directory is used) |

__Arguments__

| Name | Usage                                                                                                      |
|------|------------------------------------------------------------------------------------------------------------|
| name | The name of the action, which should be generated. Typically in the form %nomen%-%verb% (e.g. user-create) |

---

```
keeko generate:api
```

__Parameters__

| Name       | Shortcut | Usage                                                                                    |
|------------|----------|------------------------------------------------------------------------------------------|
| --schema   | -s       | Path to the database schema (if ommited, database/schema.xml is used)                    |
| --composer |          | Path to the composer.json (if ommited, composer.json from the current directory is used) |

---

```
keeko magic
```

__Parameters__

| Name       | Shortcut | Usage                                                                                    |
|------------|----------|------------------------------------------------------------------------------------------|
| --schema   | -s       | Path to the database schema (if ommited, database/schema.xml is used)                    |
| --composer |          | Path to the composer.json (if ommited, composer.json from the current directory is used) |

## Code Variables

### Package

| Variable              | Example      | Type   | Description                                                          |
|-----------------------|--------------|--------|----------------------------------------------------------------------|                                                                   |
| packageName           | `user`       | String | Second part of the full package name                                 |
| fullPackageName       | `keeko/user` | String | Full package name w/ vendor                                          |
| vendorName            | `keeko`      | String | Vendor part of the full package name                                 |

### Model

| Variable              | Example    | Type   | Description                                                          |
|-----------------------|------------|--------|----------------------------------------------------------------------|
| modelName             | `user`     | String | a `snake_case` representation                                        |
| modelObjectName       | `User`     | String | a `StudlyCase` representation (typically used as class/object name)  |
| modelPluralName       | `users`    | String | a plural version of  `snake_case` (when plural is needed, docs etc.) |
| modelObjectPluralName | `Users`    | String | a plural version of `StudlyCase` (when plural is needed, docs etc.)  |
| tableName             | `kk_user`  | String | a `snake_case` name used in the propel model (with prefix)           |
| model                 | `[Object]` | Table  | Propel object of the model                                           |
| type                  |            | String | A value of (list|create|update|read|delete)                          |
