# Linux安装vsftp与配置
> VSFTP是一个基于GPL发布的类Unix系统上使用的FTP服务器软件，它的全称是Very Secure FTP 从此名称可以看出来，编制者的初衷是代码的安全。
## 一、安装vsftp服务

1、安装vsftp
```cmd
yum -y install vsftpd
```
2、测试是否安装成功
```cmd
service vsftpd start
```
如果启动成功，并且不报错，就代表安装成功。
## 二、配置vsftpd服务
1、安装成功后，默认会在``/etc/vsftpd``目录下生成``vsftpd.conf``这个配置文件
```cmd
# 允许匿名登录 这里设置不允许匿名登录
anonymous_enable=NO 
# 允许本地用户登录
local_enable=YES
# 本地用户的写权限
write_enable=YES
# 使用FTP的本地文件权限,默认为077
# 一般设置为022
local_umask=022
# 切换目录时
# 是否显示目录下message的内容
dirmessage_enable=YES
# 启用FTP数据端口的数据连接
connect_from_port_20=YES
# 运行在独立模式和IPv4套接字监听 此指令不能与listen_ipv6指令同时使用。
listen=yes
```
2、上面这么多其实我就压根没设置什么，都是系统默认的，具体看自己的配置文件，按照自己的需求进行设置，重要的是下面这些配置项（写黑板，记笔记了）：

现在我需要将用户登录ftp后设置在自己家目录活动，也就是限制用户只能在自己家目录进行一系列操作，不能去到根目录以及其他目录，就要设置如下选项：
```cmd
# 设置所有用户只能在自己家目录活动
#chroot_local_user=YES
# 调用限制在家目录的用户名单
chroot_list_enable=YES
# 限制在家目录的用户名单所在路径 ，想限制谁，就把谁放在/etc/vsftpd/chroot_list这个文件里面 注意，一行一个用户
chroot_list_file=/etc/vsftpd/chroot_list

```
配置完成后，重启vsftp。
这里有一点需要注意：
> ①如果开启``chroot_local_user``这个选项，所有用户都被限制在其主目录下。那么，``chroot_list_enable``这个选项的意思就是在``/etc/vsftpd/chroot_list``这些用户作为“例外”，不受限制。

> ②如果关闭``chroot_local_user``这个选项，所有用户都不会被限制。那么，``chroot_list_enable``这个选项的意思就是在``/etc/vsftpd/chroot_list``这些用户也作为“例外”，受活动范围的限制。
上面这两点都是基于``chroot_list_enable``开启的情况下说明的。
## 三、添加ftp用户
这里我需要添加的这个ftp用户只能登录ftp，不能登录shell，添加用户的时候就需要注意：
```cmd
# 添加用户时设置用户不能登录shell
useradd -s /sbin/nologin ftpuser 
# 已存在一个用户，设置不能登录shell
usermod -s /sbin/nologin ftpuser
```
允许ftp用户ssh登录
```cmd
usermod -s /bin/bash ftpuser 
```
将添加的这个用户放到``/etc/vsftpd/chroot_list``这个文件里面，限制只能在家目录活动，重启vsftp。用ftp软件连接。
如果连接不上，请检查selinux和防火墙规则，自行配置。
关掉selinux：
```cmd
vi /etc/selinux/config
SELINUX=enforcing 设置成SELINUX=disabled
```
到此，一个ftp服务器搭建完成。
 
