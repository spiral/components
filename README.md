Spiral Core Components
================================

[![Latest Stable Version](https://poser.pugx.org/spiral/components/v/stable)](https://packagist.org/packages/spiral/components) 
[![Total Downloads](https://poser.pugx.org/spiral/components/downloads)](https://packagist.org/packages/spiral/components)
[![License](https://poser.pugx.org/spiral/components/license)](https://packagist.org/packages/spiral/components)
[![Build Status](https://travis-ci.org/spiral/components.svg?branch=master)](https://travis-ci.org/spiral/components)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spiral/components/badges/quality-score.png)](https://scrutinizer-ci.com/g/spiral/components/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/spiral/components/badge.svg?branch=feature/pre-split)](https://coveralls.io/github/spiral/components?branch=feature/pre-split)

<b>[Documentation](http://spiral-framework.com/guide)</b> | [Framework Bundle](https://github.com/spiral/spiral) | [Skeleton Application](https://github.com/spiral/application)

# Components included
  - Core interfaces and autowiring DI
  - Stempler template processor
  - Tokenizer, class locator, invocation locator
  - Debug, profiling and dump components
  - FileManager and Abstract Storage (Amazon, Rackspace, SFTP, FTP, GridFS)
  - Pagination
  - DBAL, schema introspection, comparation, scaffolding
  - Iehahrical ODM
  - ORM, schema scaffolding, eager loading, transactional active record, memory mapping
  - Security (NIST RBAC)
  - Code scaffolding

# Running Tests
Install component dependencies first, make sure you have proper .env file with details about
connected databases and storage component server configurations, you can find sample env in `.env.sample`,
DO NOT commit your .env into repository. To run tests execute:

```
phpunit
```

## Verbose Testing
In order to enable additional profiling mechanisms in spiral tests set following variable in your 
env configuration:

```
PROFILING = true
```

This is enable echoing of Storage, Database and ORM component log messages.
![Profiling](http://image.prntscr.com/image/539b6b6ae59a4aceaf86bf1747c994fb.png)
