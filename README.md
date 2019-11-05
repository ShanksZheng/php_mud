php_mud是一个php开发的轻量级mud游戏框架，可以帮助你快速开发出你的mud游戏。
注意，由于是常驻内存程序，只支持cli模式运行。

目录结构：  
BasicLib		存放文件类，日志类，定时器等基础类  
SocketLib   存放网络类库和socket辅助函数  
SimpleMUD	框架的核心代码  
enemies		怪物数据库  
item			物品数据库  
logs			游戏日志和异常日志  
maps			地图数据库  
store			商店数据库  
game.data	记录游戏配置  
main.php		入口文件  