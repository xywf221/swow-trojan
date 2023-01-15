闲的没事干用swow重写了一下其实也可以用swoole重写跟swow绑定程度不是很高

目前 Server 模块可以正常使用 

### 如何运行
`composer run dev` or `php -d extension=swow bin/trojan`

具体端口跟证书配置可以看 `config/config.yaml`

### 待实现模块

- [x] Server
- [ ] Client
- [ ] Nat