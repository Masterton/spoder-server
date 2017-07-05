# 爬虫服务器

## spider refer website list
----------------------
1. [PHP Simple HTML DOM Parser vs FriendsOfPHP Goutte](http://vegibit.com/php-simple-html-dom-parser-vs-friendsofphp-goutte/)
2. [What Is Goutte?](http://vegibit.com/what-is-goutte/)
3. [PHP Scraping Tutorial - Scrape Reddit With Goutte](http://www.automationfuel.com/php-scraping-tutorial-scrape-reddit-with-goutte/)
4. [WEB SCRAPING 101 WITH PHP AND GOUTTE](http://safeerahmed.uk/web-scraping-101-with-php-and-goutte)
5. [Scrape the web with Goutte](https://incarnated.net/blog/scrape-web-goutte/)

## spider refer document/repository list
1. [The DomCrawler Component](https://symfony.com/doc/current/components/dom_crawler.html)
2. [Goutte, a simple PHP Web Scraper](https://github.com/FriendsOfPHP/Goutte)
3. [DomCrawler Component](https://github.com/symfony/dom-crawler)

## mongodb refer document/repository list
1. [GridFS](https://docs.mongodb.com/php-library/master/tutorial/gridfs/)
2. [MongoDB driver](http://php.net/manual/zh/set.mongodb.php)
3. [MongoDB library for PHP](https://github.com/mongodb/mongo-php-library)

## 服务模块、流程、运作模式说明
* [server]:
    - [gateway]:(10203) --> 接受外部socket请求
    - [task]:(9721) --> 执行任务，内部(gateway、http)调用
    - [http]:(9720) --> 接受外部http请求
    - [data]:(10200) --> 不同进程(甚至服务器)间的数据共享
    - [register]:(10201) --> 执行[gateway]分配的任务，与[gateway]通信
* [client]:
    - [http] 接受页面http请求
    - [sockio] 接受页面WebSocket(socketio封装过的)请求
* [server]的socket服务模式:
    1. [client]--(crawl-params)-->[server.gateway]
    2. [server.gateway]-->[server.register.(`\Spider\Server\Events`)]-->(onMessage)-->(socket_send_task)-->[task]
    3. [task-->onMessage]->[`\Spider\Crawler\Core`]-->(execute)-->[task->onMessage]-->(send_task-->onMessage)-->[server.gateway]-->[client]
        * (execute-->get_plugin) 加载插件，用于爬取完成之后解析数据
        * (execute-->get_page) 爬取网页，完成之后使用加载了的插件解析数据并返回
    4. [client]->(onMessage)->(*save-data-to-db*)
* [server]的http服务模式:
    1. [client]--(crawl-params)-->[server.http]
    2. [server.http]-->(http_send_task)-->[task]
    3. [task-->onMessage]->[`\Spider\Crawler\Core`]-->(execute)-->[task->onMessage]-->(**http-callback**)-->[server.gateway]-->[client]->(*save-data-to-db*)
