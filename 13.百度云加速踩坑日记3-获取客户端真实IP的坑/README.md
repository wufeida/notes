# 百度云加速踩坑日记3-获取客户端真实IP的坑
> 作为一个极客男，总是会对一些东西追求完美，当然，追求的过程是要付出一些代价的。
## 挖坑
百度云加速设置完成之后，

网站运行基本没有问题，

加速之后效果那是相当明显，

静态资源全部从CDN加速服务器缓存获取，

极大的降低了服务器压力，

网站访问速度也有了一个质的提升。

但是当我查看后台的访问日志时，

发现全是天津的ip访问记录（我的ip一个没有），

我明明是在京城（北京），

为什么都是天津的ip？
## 填坑
我的项目使用laravel框架写的（最优雅的PHP框架），

laravel有一个获取客户端ip的方法``$request->getClentIp()``，

我全部用的是这个方法获取客户端ip的，经过查看该方法源码，
```php
public function getClientIps()
    {
        $ip = $this->server->get('REMOTE_ADDR');
    }
```
它其实获取的就是``$_SERVER['REMOTE_ADDR']``，

> REMOTE_ADDR 是你的客户端跟你的服务器“握手”时候的IP。如果使用了“匿名代理”，REMOTE_ADDR将显示代理服务器的IP。 

由于使用了CDN加速，

通过``$_SERVER['REMOTE_ADDR']``获取到的其实就是代理服务器的IP，

这里有一个获取真实IP（PHP）的方法：
```php
function GetIp(){
	    $realip = '';
	    $unknown = 'unknown';
	    if (isset($_SERVER)){
	        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
	            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	            foreach($arr as $ip){
	                $ip = trim($ip);
	                if ($ip != 'unknown'){  
	                    $realip = $ip;
	                    break;
	                }
	            }
	        }else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)){
	            $realip = $_SERVER['HTTP_CLIENT_IP'];
	        }else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)){
	            $realip = $_SERVER['REMOTE_ADDR'];
	        }else{
	            $realip = $unknown;
	        }
	    }else{
	        if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)){
	            $realip = getenv("HTTP_X_FORWARDED_FOR");
	        }else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)){
	            $realip = getenv("HTTP_CLIENT_IP");
            }else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)){
	            $realip = getenv("REMOTE_ADDR");
            }else{
	            $realip = $unknown;
	        }
	    }
	    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
	    return $realip;
	}  
```
改成这个后完美解决。
## 理解
> CDN的全称是Content Delivery Network，即内容分发网络。其基本思路是尽可能避开互联网上有可能影响数据传输速度和稳定性的瓶颈和环节，使内容传输的更快、更稳定。通过在网络各处放置反向代理节点服务器所构成的在现有的互联网基础之上的一层智能虚拟网络，CDN系统能够实时地根据网络流量和各节点的连接、负载状况以及到用户的距离和响应时间等综合信息将用户的请求重新导向离用户最近的服务节点上。其目的是使用户可就近取得所需内容，解决 Internet网络拥挤的状况，提高用户访问网站的响应速度。

**CDN网络是在用户和服务器之间增加Cache层，**

**将用户的请求引导到CDN节点上，**

**先去检查Cache层有没有缓存数据，**

**如果有缓存数据并且没有过期，**

**直接返回缓存的数据，**

**不需要再去请求源服务器；**

**如果没有缓存数据或者缓存数据已经过期，**

**就去源服务器请求数据，**

**返回给客户端，**

**给自己也缓存一份。**

**这是CDN部分工作流程。完整工作流程请看[这里]()**

明白这些后，这样问题就不难理解了，

这里如果用``$_SERVER['REMOTE_ADDR']``获取客户端IP的话，

其实都是CDN节点的IP，

并非客户端真实IP。

获取真实IP的方法（由百度云加速只能机器人提供）：

    *真实IP：为解决这个问题，可以通过在云加速转发的HTTP头信息中增加 X-Forwarded-For 信息，用于记录客户端的真实IP，这时web服务器的日志就可以使用 $http_x_forwarded_for变量记录远程客户端的真实IP。格式如下：
    
     Nginx
     '$http_x_forwarded_for - $remote_user [$time_local] "$request" ''$status $body_bytes_sent "$http_referer" ''"$http_user_agent" ';
     
     Apache
     格式如下：
     LogFormat "%{X-Forwarded-For}i %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\""
     
     ASP
     Request.ServerVariables("HTTP_X_FORWARDED_FOR")
     
     PHP
     $_SERVER["HTTP_X_FORWARDED_FOR"]
     
     JSP
     request.getHeader("HTTP_X_FORWARDED_FOR")*
     
## 总结
只要理解了CDN工作原理，解决这个轻而易举，你们踩过这个坑吗？