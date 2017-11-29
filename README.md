# 104isgd-apim-framework

## 系統需求

執行環境

* PHP 7.1+

## 快速安裝

接著在您的專案目錄裡，執行以下指令：

```shell
composer config repositories.apim-framework vcs git@github.com:104corp/104isgd-apim-framework.git
composer config -g github-oauth.github.com <access_token>
```

> `<access_token>` 是剛剛建立的 GitHub OAuth Token

Composer 設定完成後，即可使用 require 安裝套件 

```shell
composer require 104corp/apim-framework:dev-master
```
