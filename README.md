# 問題点
- Windowsでスクリプト実行すると文字コードがめんどくさいことになっているのでどうしようか考え中。
- Google Places APIの無料で叩ける回数がかなり少ないのでむやみに実行しないこと

# ツールの使い方
## APIキー登録
1. api_key.json.orgをコピー、リネームしてapi_key.jsonにする
1. Google Places API用のキーを"GOOGLE_PLACES_API_KEY"のペアに入力
1. イベントバンクAPI用のキーを"EVENT_BANK_API_KEY"のペアに入力。

## イベントバンク
未実装

## GooglePlaceAPI
1. queries.jsonに検索したいキーワードを記述、utf-8で保存(文字コードの問題の苦肉の策)
1. コマンドを実行 `$ php place.php`
1. 同フォルダにキーワード.jsonのファイルが出来上がる(ただしWindowsではファイル名が文字化けする)
