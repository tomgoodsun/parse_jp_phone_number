# parse_jp_phone_number
日本国内のハイフン無しの電話番号をハイフン有りの形式に変換するサンプル

ひとまずウェブアクセス出来るとことに配置して`sample.php`にアクセスしてみてください。
`index.php`は全パターンテストが入っています。

## 使い方
```php:sample.php
<?php
require(__DIR__ . '/libs/include.php');

// パーサーを作成
$parser = new \TelephoneNumberParser(__DIR__ . '/setting.yml');

// パースすると結果は配列で返ってきます。
$result = $parser->parse('09012345678'); // 090-1234-5678

header('Content-type: text/plain');
var_export($result);
```

上記を実行すると以下のようになります。

```
array (
  'is_error' => false,
  'original' => '09012345678',
  'format' => 
  array (
    'category' => '0A0-CDE-FGHJK',
    'length' => 11,
    'regexp' => '^0(2|[5-9])0[1-9][0-9]+$',
    'digits' => 
    array (
      0 => 3,
      1 => 4,
      2 => 4,
    ),
  ),
  'splitted' => 
  array (
    0 => '090',
    1 => '1234',
    2 => '5678',
  ),
  'joined' => '090-1234-5678',
)
```

# 2017年8月22日：固定電話番号の市外局番、市内局番のデータベースをExcelファイルから再作成するスクリプトを追加

ja_com_spec_master.sqlite3を再作成するスクリプトを追加しました。
update_ma_list.phpにアクセスすると再構築をします。
tmpフォルダ以下にファイルがある場合はそれを使用します。ない場合は総務省HPからダウンロードします。
