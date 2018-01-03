# 百度云加速踩坑日记1-图片延迟加载的坑

## 挖坑：
我的网站使用了``photoswipe``插件，这是一个图片查看插件，使用过这个插件的都知道，需要给``img``标签父级a标签添加``data-size``属性，值就是图片的宽x高，我这里使用js动态的给所有img父级a标签添加这个属性
```javascript
function auto_data_size(){
    $(".am-article-bd img").each(function() {
        var imgs = new Image();
        imgs.src=$(this).attr("src");
        var w = imgs.width,
            h =imgs.height;
        $(this).parent("a").attr("data-size",w+"x"+h);
    })
};
```
在这里pc端没有任何问题，宽高可以获取，但是一用手机浏览，图片就点不开，用谷歌浏览器切换到手机模拟查看源码，发现了问题，``data-size``全部是0x0，这个肯定就有问题，但是为啥电脑端就没事呢？纠结了好久，反复看手机端源码之后，在``img``标签里面发现了一个``data-cfsrc``属性，属性值就是图片的地址，我的代码里压根没有写过这个属性啊，怎么会多出来这个属性，仔细想了想，会不会百度云加速影响的
