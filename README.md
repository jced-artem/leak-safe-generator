# Leak safe PHP generator wrapper

### Install
`composer require jced-artem/leak-safe-generator`

If you want to prevent memory leaks when you use generators with handlers and you bored to use `try..finally` constructions - this class could help.

### Example
```
function parsePages() {
    $ch = curl_init();
    // ... some options
    while ($condition) {
        yield curl_exec($ch);
    }
    curl_close($ch);
}

foreach (parsePages() as $page) {
    if ($someCondition) {
        break;
    }
    // ...
}
```
### Problem
If you break loop before get all yields you will have not closed handler `$ch` and this can cause memory leaks.

### Solution
```
function parsePages() {
    $ch = curl_init();
    // ... some options
    try {
        $finished = false;
        while ($condition) {
            yield curl_exec($ch);
        }
        $finished = true;
    } finally {
        // close anyway
        curl_close($ch);
        if ($finished) {
            // do something if reached last element
        } else {
            // do something on break
        }
    }
}
```
### New problem
This code isn't usable if you make several generators

### New solution
```
$lsg = new LeakSafeGenerator();
$lsg
    ->init(function() {
        $this->ch = curl_init();
        // ... some options
        while ($condition) {
            yield curl_exec($this->ch);
        }
    })
    ->onInterrupt(function() {
        // do something on break
    })
    ->onComplete(function() {
        // do something if reached last element
    })
    ->onFinish(function() {
        curl_close($this->ch);
    })
;
foreach ($lsg->getGenerator() as $page) {
    if ($someCondition) {
        break;
    }
    // ...
}
```
or shorter version
```
$lsg = new LeakSafeGenerator(
    function() {
        $this->ch = curl_init();
        // ... some options
        while ($condition) {
            yield curl_exec($this->ch);
        }
    },
    function() {
        curl_close($this->ch);
    }
);
```
And you don't need to create additional functions/methods and write `try..finally` constructions for each generator.
