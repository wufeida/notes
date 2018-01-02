# PHP开启opcache，让你的应用飞起来
> OPcache通过将 PHP 脚本预编译的字节码存储到共享内存中来提升 PHP 的性能， 存储预编译字节码的好处就是 省去了每次加载和解析 PHP 脚本的开销。PHP 5.5.0 及后续版本中已经绑定了 OPcache 扩展。 对于 PHP 5.2，5.3 和 5.4 版本可以使用 PECL扩展中的 OPcache 库。
## 一、设置OPcache扩展路径
找到php.ini这个配置文件，设置OPcache扩展路径(例如我的):
```cmd
zend_extension=/usr/local/php/lib/php/extensions/no-debug-non-zts-20160303/opcache.so

```
## 二、 详细配置OPcache
```cmd
opcache.enable=1
```
启用opcache，默认是关闭的。

```cmd
opcache.memory_consumption=512
```
这个配置表示你想要分配给 OPcache 的内存空间（单位：MB），设置一个大于 64 的值即可。
```cmd
opcache.interned_strings_buffer=64
```
这个配置表示你想要分配给实际字符串的空间（单位：MB），设置一个大于 16 的值即可。
> PHP使用了一种叫做字符串驻留（string interning）的技术来改善性能。例如，如果你在代码中使用了1000次字符串“foobar”，在PHP内部只会在第一使用这个字符串的时候分配一个不可变的内存区域来存储这个字符串，其他的999次使用都会直接指向这个内存区域。这个选项则会把这个特性提升一个层次——默认情况下这个不可变的内存区域只会存在于单个php-fpm的进程中，如果设置了这个选项，那么它将会在所有的php-fpm进程中共享。在比较大的应用中，这可以非常有效地节约内存，提高应用的性能。
```cmd
opcache.max_accelerated_files=32531
```
这个配置表示可以缓存多少个脚本，将这个值尽可能设置为与项目包含的脚本数接近（或更大）。
> 这个选项用于控制内存中最多可以缓存多少个PHP文件。这个选项必须得设置得足够大，大于你的项目中的所有PHP文件的总和。我的代码库大概有6000个PHP文件。
  
 > 你可以运行``find . -type f -print | grep php | wc -l``这个命令来快速计算你的代码库中的PHP文件数。
 ```cmd
 opcache.revalidate_freq=2
 ```
 这个选项用于设置缓存的过期时间（单位是秒），当这个时间达到后，opcache会检查你的代码是否改变，如果改变了PHP会重新编译它，生成新的opcode，并且更新缓存。值为“0”表示每次请求都会检查你的PHP代码是否更新（这意味着会增加很多次stat系统调用，译注：stat系统调用是读取文件的状态，这里主要是获取最近修改时间，这个系统调用会发生磁盘I/O，所以必然会消耗一些CPU时间，当然系统调用本身也会消耗一些CPU时间）。可以在开发环境中把它设置为0，生产环境下不用管，因为下面会介绍``opcache.validate_timestamps``这个选项。
```cmd
opcache.validate_timestamps=0
```
改配置值用于重新验证脚本，如果设置为 0（性能最佳），需要手动在每次 PHP 代码更改后手动清除 OPcache。如果你不想要手动清除，可以将其设置为 1 并通过 ``opcache.revalidate_freq`` 配置重新验证间隔，这可能会消耗一些性能，因为需要每隔 x 秒检查更改。
```cmd
opcache.save_comments=1
```
这个配置会在脚本中保留注释，我推荐开启该选项，因为一些库依赖于这个配置，并且我也找不出什么关闭它的好处。
```cmd
opcache.fast_shutdown=1
```
这个配置从字面上理解就是“允许更快速关闭”。它的作用是在单个请求结束时提供一种更快速的机制来调用代码中的析构器，从而加快PHP的响应速度和PHP进程资源的回收速度，这样应用程序可以更快速地响应下一个请求。把它设置为1就可以使用这个机制了。

最终我们的优化配置为：
```cmd
opcache.enable=1
opcache.memory_consumption=512
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=32531
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```
至于这些配置都是基于我自己的服务器所配置，具体配置请根据自己的项目和服务器状况自行配置，我这里只提供一个解释和参考。

最后，保存这个配置文件并重启``php-fpm``，你会发现，哇，应用飞起来了。
