# Redis源码安装
#### Redis是一个开源的使用ANSI,C语言编写、支持网络、可基于内存亦可持久化的日志型、Key-Value数据库，并提供多种语言的API。
## 1、下载源码包
#### 进入官网下载页面
> https://redis.io/download
#### 进入服务器放源码包的文件夹

```cmd
wget http://download.redis.io/releases/redis-4.0.6.tar.gz
```

## 2、解压安装
#### 解压进入目录进行编译

```cmd
tar -zxvf redis-4.0.6.tar.gz
cd redis-4.0.6
make
```
#### 编译完成后进入src目录，执行make install

```cmd
cd src/
make install
```
#### 至此redis的安装就完成了，现在运行redis-cli,出现如下情况 Could not connect to Redis at 127.0.0.1:6379: Connection refused的错误：
![Could not connect to Redis at 127.0.0.1:6379: Connection refused](./img/redis2.png)
#### 这是因为redis服务未启动，我们先来配置redis的配置文件
## 3、配置文件
#### 我们回到/root/redis/目录里面，查看redis.conf文件
```cmd
vim redis.conf
```
#### 这个文件内容很多，我们主要看daemonize和pidfile这两个设置
#### daemonize 是否daemon化，显然我们要改成yes
#### pidfile 是redis服务判断是否运行的文件，这里我们默认就行，/var/run/redis_6379.pid
#### 设置完后保存退出
#### 有了基本配置，redis还需要有一个管理启动、关闭、重启的一个脚本。redis源码里其实已经提供了一个初始化脚本，位置在/root/redis/utils/redis_init_script
## 4、配置redis管理和自启动
#### 我们看下这个文件
```cmd
#!/bin/sh
#
# Simple Redis init.d script conceived to work on Linux systems
# as it does use of the /proc filesystem.

REDISPORT=6379
EXEC=/usr/local/bin/redis-server
CLIEXEC=/usr/local/bin/redis-cli

PIDFILE=/var/run/redis_${REDISPORT}.pid
CONF="/etc/redis/${REDISPORT}.conf"

case "$1" in
    start)
        if [ -f $PIDFILE ]
        then
                echo "$PIDFILE exists, process is already running or crashed"
        else
                echo "Starting Redis server..."
                $EXEC $CONF
        fi
        ;;
    stop)
        if [ ! -f $PIDFILE ]
        then
                echo "$PIDFILE does not exist, process is not running"
        else
                PID=$(cat $PIDFILE)
                echo "Stopping ..."
                $CLIEXEC -p $REDISPORT shutdown
                while [ -x /proc/${PID} ]
                do
                    echo "Waiting for Redis to shutdown ..."
                    sleep 1
                done
                echo "Redis stopped"
        fi
        ;;
    *)
        echo "Please use start or stop as first argument"
        ;;
esac
```

#### 我们可以看到CONF这个选项，我们遵循该文件默认的配置项执行，配置文件应该放在/etc/redis/端口号.conf
```cmd
cd /etc
mkdir redis
cp /root/redis/redis.conf /etc/redis/6379.conf 
```
#### 然后我们将redis提供的初始化脚本拷贝到/etc/init.d/redisd
```cmd
cp /root/redis/utils/redis_init_script /etc/init.d/redisd 
```
#### 在/etc/init.d下的脚本都是可以在系统启动是自动启动的服务，而现在还缺一个系统启动时的配置：
```cmd
chkconfig redisd on
```
#### 此时发现service redisd does not support chkconfig这个错误，不支持chkconfig,我们此时需要改一下redisd这个文件，加上如下配置：
```cmd
#!/bin/sh
# chkconfig: 2345 90 10 
```
#### 保存退出后重新运行chkconfig，发现不报错了，这就OK了。
#### 我们可以查看一下设置开启启动是否成功,执行如下命令：
```cmd
chkconfig
```
#### 看到这个
```cmd
redisd 	0:off	1:off	2:on	3:on	4:on	5:on	6:off
```
#### 这个表示2345启动级别都可以自启动
## 5、启动测试
#### 运行启动命令：
```cmd
service redisd start
```
#### 看到如下信息
```cmd
[root@izm5e1iujuip9pbkkg4lutz init.d]# service redisd start
Starting Redis server...
8585:C 07 Dec 10:41:21.246 # oO0OoO0OoO0Oo Redis is starting oO0OoO0OoO0Oo
8585:C 07 Dec 10:41:21.247 # Redis version=4.0.6, bits=64, commit=00000000, modified=0, pid=8585, just started
8585:C 07 Dec 10:41:21.247 # Configuration loaded
```
#### 这就启动成功了。
#### 停止命令为：
```cmd
service redisd stop
```
#### 最后你可以重启一下系统，测试一下redis服务是否可以正常启动。
#### 至此，redis安装完成。
#### 参考文章：
> https://www.cnblogs.com/zhxilin/p/5892678.html