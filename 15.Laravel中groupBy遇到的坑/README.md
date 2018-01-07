# Laravel中使用groupBy遇到的坑
> 踩坑踩坑，踩踩更健康！
## 挖坑
在我的博客项目中需要添加一个文章归档功能，

- 查询所有已发布文章
- 按月进行分组统计

laravel里面这样进行分组：
```php
groupBy(DB::raw('date_format(published_at,"%Y-%m")'))
```
运行的时候报了这样的一个错：
```php
SQLSTATE[42000]: Syntax error or access violation: 1055 'blog.articles.id' isn't in GROUP BY (SQL: select * from `articles` where `is_draft` = 0 and `published_at` < 2017-12-26 22:56:10 and `articles`.`deleted_at` is null group by date_format(published_at,"%Y-%m"))
```

错误内容是这个文章id不在``groupBy``里面，

上网查询类似问题，

说是mysql的安全模式导致的。

禁用安全模式或者修改``sql_mode``去掉``ONLY_FULL_GROUP_BY``这个值就可以

当我查看mysql选项时：
```mysql
select @@sql_mode;  
```
里面并没有值，

也就是我的mysql安全模式没有开启，

这就奇怪了，

明明没有开启安全模式怎么还会报这个错？

## 填坑

由于mysql安全模式没有开启，

我就想用原生语句试试这样查询看能查询出来不，

运行如下语句
```mysql
select * from `articles` where `is_draft` = 0 group by date_format(published_at,"%Y-%m");
```
可以查询出来，

这样的话就可以排除mysql的问题，

那真相只有一个，

是不是laravel默认把安全模式开启了，

打开``config/database.php``配置文件:
```php
'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
```
果然没有猜错，

``mysql``里面的``strict``设置为``true``，

默认开启了``mysql``的**严格模式**，

我给这里设置``false``，

完美解决问题。

## 理解

这里来看看``sql_model``是个什么东东，

``sql_model``:简而言之就是它定义了你MySQL应该支持的sql语法，对数据的校验等等。

``sql_mode``值的含义：

1. ``ONLY_FULL_GROUP_BY``：对于GROUP BY聚合操作，如果在SELECT中的列，没有在GROUP BY中出现，那么这个SQL是不合法的，因为列不在GROUP BY从句中
2. ``STRICT_TRANS_TABLES``:在该模式下，如果一个值不能插入到一个事务表中，则中断当前的操作，对非事务表不做任何限制
3. ``NO_ZERO_IN_DATE``：在严格模式，不接受月或日部分为0的日期。如果使用IGNORE选项，我们为类似的日期插入'0000-00-00'。在非严格模式，可以接受该日期，但会生成警告。
4. ``NO_ZERO_DATE``:在严格模式，不要将 '0000-00-00'做为合法日期。你仍然可以用IGNORE选项插入零日期。在非严格模式，可以接受该日期，但会生成警告
5. ``ERROR_FOR_DIVISION_BY_ZERO``:在严格模式，在INSERT或UPDATE过程中，如果被零除(或MOD(X，0))，则产生错误(否则为警告)。如果未给出该模式，被零除时MySQL返回NULL。如果用到INSERT IGNORE或UPDATE IGNORE中，MySQL生成被零除警告，但操作结果为NULL。
6. ``NO_AUTO_CREATE_USER``:防止GRANT自动创建新用户，除非还指定了密码。
7. ``NO_ENGINE_SUBSTITUTION``:如果需要的存储引擎被禁用或未编译，那么抛出错误。不设置此值时，用默认的存储引擎替代，并抛出一个异常
8. ``NO_AUTO_VALUE_ON_ZERO``:该值影响自增长列的插入。默认设置下，插入0或NULL代表生成下一个自增长值。如果用户 希望插入的值为0，而该列又是自增长的，那么这个选项就有用了。
9. ``PIPES_AS_CONCAT``: 将"||"视为字符串的连接操作符而非或运算符，这和Oracle数据库是一样的，也和字符串的拼接函数Concat相类似
10. ``ANSI_QUOTES``:启用ANSI_QUOTES后，不能用双引号来引用字符串，因为它被解释为识别符

**如果使用mysql，为了继续保留大家使用oracle的习惯，可以对mysql的sql_mode设置如下：**
在my.cnf添加如下设置：
```cmd
[mysqld]
sql_mode='ONLY_FULL_GROUP_BY,NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,PIPES_AS_CONCAT,ANSI_QUOTES'
```
