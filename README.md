
# Make Repository
Laravel repository generator for 8.0+ versions


## Installation

Install with Composer

```bash
 composer require longtnt/make-repository
```
    
## Usage/Examples

### 1.Generate only Repository
```php
php artisan make:repo Test
```
### 2.Generate Repository + Model
```php
php artisan make:repo Test --model=Test
```
### 3.Generate Repository + Contract
```php
php artisan make:repo Test --model=Test --contract
php artisan make:repo Test --model=Test -c
```


