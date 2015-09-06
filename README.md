### 3N*tr0π Password Lib

This library is a tool for calculating the strength of passwords in a uniformed manner. Instead of stupid, annoying
 requirements of your users to have at least 1 capital, at least 1 number etc in their password; this library gives a
 complexity score for the password which you can use to decide if the password is strong enough for your application's
 needs.

### Usage

```
<?php
$e = new \Carnage\Entropy\Entropy();
echo $e->calculateScore('Password1');
//1.5440680443503
echo $e->calculateScore('12t1^7kl0');
//16.434673224307
```