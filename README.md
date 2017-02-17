# stasis
Static site generation tool using twig templates.


## Installation

```bash
$ composer require shineunited/stasis
```

## Usage

The stasis command will reside in composer's vendor/bin directory.

```bash
$ ./vendor/bin/stasis <command> <arguments>
```

#### build
```bash
$ stasis build ./source ./target --verify --clean
```

#### clean
```bash
$ stasis clean ./target
```

#### verify
```bash
$ stasis verify ./source
```
