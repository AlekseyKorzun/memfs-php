MemFS-PHP
===========

This package provides a way to store your application files in Memcached pool with automatic
parsing using eval() upon retrieval.

I wrote this after trolling a friend of mine how storing files in Memcached is faster than including
them from disk, so while there are definite uses for this under right circumstances make sure you understand
what you are doing.

Performance
===========

First let's look at benchmarks running in VirtualBox on MacBook Pro:

```bash
Loaded 500 files using regular include() in 0.020308017730713
Loaded 500 files using MemFS in 0.15591597557068
Loaded 500 files using MemFS (10 files at the time) 0.018874883651733
Loaded 500 files using MemFS (250 files at the time) 0.015585899353027
Loaded 500 files using MemFS (500 files at the time) 0.016402959823608
```

Keep in mind that the laptop in question has SSD drive. 

As you can see, when compared directly one for one you might get performance degration but as you 
scale up and request 10+ files you will see a slight speed increase of about 0.00143313407 seconds.

Not much but this benchmark is not scientific and was performed on a empty machine with SSD drive with no I/O 
load. Your setup might be different and if you take 0.0014 * requests per seconds your application is processing you
might see a benefit of saving 14 seconds per 10000 requests.

Use cases
===========

- Servers that host PHP based applications in a saturated or slow I/O environment.
- Distributed applications that constantly update their logic, can be updated by updating 
memory pool with new code that they will include and evaluate.
- Applications that need to load and parse more than 1 file in a consecutive order.

Memcached
===========

For optimal results, make sure server hosting cache pool is hosted on a separate server but shares the LAN 
with your web servers. 

Depending on your load the link between the servers must be greater than 100Mbps.
        
Make sure to use igBinary with latest version of daemon.

Installation
===========
If you have your own autoloader, simply update namespaces and drop the files
into your frameworks library.

For people that do not have that setup, you can visit http://getcomposer.org to install
composer on your system. After installation simply run `composer install` in parent
directory of this distribution to generate vendor/ directory with a cross system autoloader.

Benchmark
===========
You can benchmark and gauge how much benefit this optimization might bring you by running:

```php
php benchmark/run.php
```

make sure to run it twice before reading the results so the application can cache files

Bugs & Feedback
===========
Feel free to reach me via e-mail al.ko@webfoundation.net

