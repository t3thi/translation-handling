# TYPO3 Translation Handling Initiative Extension

This repository contains the **TYPO3 Translation Handling Initiative Extension**, a testing tool to easily reproduce translation-handling-related use cases in the latest TYPO3 Core. The extension is inspired by the official [styleguide extension](https://github.com/TYPO3-CMS/styleguide/).

---

## Purpose

The extension provides a quick and reproducible way to set up TYPO3 instances with predefined translation-related test cases. It is intended to support developers and integrators in validating concepts, testing edge cases, and experimenting with translation handling in TYPO3.

---

## Scope

* Generates demo sites and content structures for translation handling
* Provides CLI commands for creating and managing test datasets
* Includes example setups to simulate common and complex scenarios

---

## CLI Commands

The extension registers two commands in the `translation-handling` namespace. Both commands accept an **optional** `type` argument to select which scenario(s) to operate on.

Both commands initialize backend authentication for the `_cli_` user via TYPO3’s bootstrap before executing their actions.

### Create page trees

Create page tree(s) with translation handling examples.

**Signature**

```bash
typo3 translation-handling:create [type]
```

**Argument**

* `type` — Which scenario to create. Valid values:

    * `fallback` – Generate a site/tree demonstrating a **fallback** model
    * `strict`   – Generate a site/tree demonstrating a **strict** model
    * `free`     – Generate a site/tree demonstrating a **free** model
    * `all`      – Generate **all** of the above

### Delete page trees

Delete page tree(s) previously created by the `create` command.

**Signature**

```bash
typo3 translation-handling:delete [type]
```

**Argument**

* `type` — Which scenario to delete. Valid values:

    * `fallback` – Delete the site/tree demonstrating a **fallback** model
    * `strict`   – Delete the site/tree demonstrating a **strict** model
    * `free`     – Delete the site/tree demonstrating a **free** model
    * `all`      – Delete **all** of the above

---

## Target Audience

* TYPO3 Core developers working on translation features
* Extension developers validating multilingual behavior
* Integrators testing translation handling in projects

---

## Contribution

We welcome input from the TYPO3 community:

* Open [issues](https://github.com/t3thi/translation-handling/issues)
* Share real-world use cases
* Join discussions at TYPO3 Camps, DevDays, or Slack huddles

---

## Status

This repository is **work in progress**. It is a playground for experiments and not intended for production use.

---

## Related Links

* [Official Initiative Page on typo3.org](https://typo3.org/community/teams/typo3-development/initiatives/translation-handling)
* [Slack Channel #typo3-translation-handling](https://typo3.slack.com/archives/C05D7UF1L8M)
* [Meeting Notes](https://notes.typo3.org/s/f3ae8fZSD)

---

## License

This repository is licensed under the GPL v2 or later. See [LICENSE](LICENSE) for details.
