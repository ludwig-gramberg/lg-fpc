# Full Page Cache

- uses redis as a backend but other backends could be implemented
- application decides what gets cached and for how long
- supports tags and post-processing

## Requirements

- composer
- redis-server

## Configuration

- put directory lg-fpc-config in application root
- copy sample cfg-files and edit as necessary

## Usage

- prepend index.php with:  
  ``require_once 'wp-content/plugins/lg-fpc/bootstrap.php';``  
  ``require_once 'wp-content/plugins/lg-fpc/startup.php';``  
  or:
- use new entrance-script and require index.php in it

## Behaviour

1. on cache miss application decides wether or not page should be cached and which parameters are relevant
2. cache registers page with meta data of page
3. background worker fetches and stores page in cache
4. worker checks for old pages i.e. the refresh interval has been reached and fetches and stores them once again