# Replicator

Meal logging app that posts to a server using the ActivityPub client-to-server protocol, ish, with custom ActivityStreams 2.0 extension vocab.

## Run

```
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  composer install
```

## Vocab

Uses terms from the AS2 extension namespace `https://terms.rhiaro.co.uk/as#`, prefix `asext`.

Posts objects with type `as:Activity` and `asext:Consume`.

## Todo

* [ ] Get commonly used food post tags for tag options
* [ ] Make a link to /stuff posts