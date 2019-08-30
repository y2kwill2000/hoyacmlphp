# CmlPHP-快速、穩定、易維護的php框架

## 簡介

> CmlPHP從12年開始開發。從最早追求盡可能輕量，php5.2-的語法。到後面不斷總結工作中碰到的實際的問題，加入工程化的一些東西。加入Composer的支持。加入了很多可以減少程序員開發時間的一些特性。現在發佈了v2.x。提供了API快速開發的組件或者說基於CmlPHP v2.x的一個項目演示(自動從註釋生成接口文檔)。不說什麼跟xx框架比。比來比去可一點都不好玩，適合就好。這個框架是我到目前總結的盡可能提高自己開發效率的工具集(或者有更好的說法？)。提供給需要它的朋友，希望它可以幫助大家更輕鬆的完成開發的工作.

關於cmlphp的介紹也可以看看我的這篇文章:[再來聊聊cmlphp](http://www.jianshu.com/p/b03b3d72108c)

## v2.x

> CmlPHP v2.x 是一個免費的遵循apache協議的全能型php開源框架

> CmlPHP v2.x 是基於php5.3+(v2.7+要求php5.4+)版本(已經測試過php7)開發的MVC/HMVC/MVSC/HMVSC框架,支持composer、分佈式數據庫、分佈式緩存，支持文件、memcache、redis、apc等緩存，支持多種url模式、URL路由[RESTful]，支持多項目集成、第三方擴展、支持插件。

> CmlPHP v2.x 在底層數據庫查詢模塊做了緩存集成，開發者無需關注數據緩存的問題，按照相應的API調用即可獲得最大性能。從而從根本上避免了新手未使用緩存，或緩存使用不當造成的性能不佳的問題。也杜絕了多人協同開發緩存同步及管理的問題

> CmlPHP v2.x 支持根目錄、子目錄，單入口、多入口部署、支持獨立服務器、虛擬主機、VPS等多種環境，絕大部分開發環境可直接運行，無需配置偽靜態規則(部分低版本server只要修改框架URL配置即可，框架會自動處理)，快速上手開發。線上環境對SEO有要求時再配置偽靜態即可。

> CmlPHP v2.x 自帶強大的安全機制，支持多種緩存並可輕鬆切換,幫你解決開發中各種安全及性能問題，保證站點穩定、安全、快速運行

> CmlPHP v2.x 提供了詳細的開發文檔，方便新手快速入門

> CmlPHP v2.x 擁有靈活的擴展機制，自帶了常用的擴展

> CmlPHP v2.x 擁有靈活配置規則，開發、線上互不干擾

> CmlPHP v2.x 擁有簡單高效的插件機制，方便你對系統功能進行擴展

> CmlPHP v2.x 提供了簡單方便的debug相關工具方便開發調試。線上模式提供了詳細的錯誤log方便排查

> CmlPHP v2.x 適用於大、中、小各種類型的Web應用開發。API接口開發

> CmlPHP v2.x 支持Session分佈式存儲

> CmlPHP v2.x 支持守護工作進程

> CmlPHP v2.x 提供了命令運行支持

## v2.7.x
> 服務化。各個組件使用容器來管理、注入依賴。封裝了FastRoute、blade、whoops的服務可在入口中注入替換內置的相關組件(默認還是使用框架內置的)

## v2.6.x
> 從v2.6.0 正式引入MongoDB的支持

## 開發手冊
開發手冊使用gitbook編寫
[CmlPHP v2.x開發手冊](http://doc.cmlphp.com "CmlPHP v2.x開發手冊")

## 你們想要的Api文檔
> 部分看了開發手冊的朋友給我發郵件希望我提供一份詳細的Api文檔,以便更深入的學習CmlPHP，現在它來啦!! [CmlPHP v2.x Api](http://api.cmlphp.com)。

## 項目推薦目錄骨架
> 提供了基礎目錄結構及示例，[點擊這裡查看](https://github.com/linhecheng/cmlphp-demo)。

## Api項目示例
> web開發中很大一部分是接口開發，本示例包含了api開發的兩個接口示例以及根據代碼註釋自動生成文檔的示例。 [點擊這裡查看](https://github.com/linhecheng/cmlphp-api-demo)


## 視頻教程
> [CmlPHP簡介](http://v.youku.com/v_show/id_XMTQwNTc1MTI0MA==.html)
> 
> [CmlPHP項目目錄骨架及api項目演示](http://v.youku.com/v_show/id_XMTQwNTc4MDk2OA==.html)

## 聯繫我
因為工作的原因QQ用得很少，所以也就不建qq群了。有任何建議或問題歡迎給我發郵件。 linhechengbush@live.com
