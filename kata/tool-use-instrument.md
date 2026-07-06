# `tool-use-instrument`

**`#[Tool]`でResourceをLLMのtool定義として公開する** · [← 索引に戻る](../index.md)

- **Category:** External reference（外部参照実装の型）
- **Status:** `external`
- **Aliases:** Tool Use, MCP, AI instrument, #[Tool], #[Exclude], LlmClientInterface, agent loop, AIエージェント, ツール定義
- **Manual:** —（公式マニュアル章なし。learnサイト「AI Era」が背景思想）
- **Reference:** [bearsunday/BEAR.ToolUse](https://github.com/bearsunday/BEAR.ToolUse)（`composer require bear/tool-use`。このリポジトリには未インストール）
- **Use when:** BEAR.SundayのResourceを、コードを書き換えずにLLMのtool（function calling / Tool Use）として公開したい。
- **近いKata:** [`apidoc-llms-generated`](./apidoc-llms-generated.md)（Resource/schema/ALPSからのAI向け資料生成という同じSSOT原理）

## 例

参照実装は公式パッケージ [bearsunday/BEAR.ToolUse](https://github.com/bearsunday/BEAR.ToolUse)（`composer require bear/tool-use`。このリポジトリには未インストール）。実装する形: 公開したいResourceに `#[Tool(description: ...)]` を付け、公開したくないものは `#[Exclude]` で除外する。tool定義はResource classから自動生成され、parameter説明はJSON Schema・ALPS profile・PHPDocから引かれる — 説明を別途手書きしない。LLM側は `LlmClientInterface` の実装で差し替えるLLM-agnostic構成。公式マニュアル章は無く、learnサイト「AI Era」が背景思想の一次資料。

## Naming

この型に固有のクラス命名は少ない — 制御は2つのattributeと1つのinterfaceで行う:

| 役割 | 命名 |
|---|---|
| tool公開（説明付き） | `#[Tool(description: ...)]` |
| 公開除外 | `#[Exclude]` |
| LLM差し替え境界 | `LlmClientInterface` |

tool定義は生成物なので、tool名・parameter名には既存のURI・method・schemaの命名がそのまま出る（[`apidoc-llms-generated`](./apidoc-llms-generated.md) と同じ原理）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] tool定義はResource classから自動生成され、parameter説明はJSON Schema・ALPS profile・PHPDocから引かれると理解したか（説明を別途手書きしない）。
- [ ] 公開範囲は `#[Tool(description: ...)]` / `#[Exclude]` で制御すると決めたか。
- [ ] LLM側は `LlmClientInterface` 実装で差し替え（LLM-agnostic）と理解したか。

## Key points

「GETはsafe（AIが自由に呼べる）、writeは冪等性で扱いを分ける」というmethod意味論が、そのままAIのtool安全設計になる。URI・型・schemaという既存のresource定義がtool定義のSSOT — `docs/llms.txt`（[`apidoc-llms-generated`](./apidoc-llms-generated.md)）が知識面、Tool Useが実行面。

## Do not

- tool説明をResource定義と別に二重管理しない — parameter説明はJSON Schema・ALPS profile・PHPDocから生成される。手書きの説明を別に持つと、Resourceの変更に追従せずSSOTが壊れる。

## マスター確認（After）

- [ ] `#[Tool]` 付きResourceのtool定義が生成され、`#[Exclude]` が反映されることを自プロジェクトのtestでpinしてgreen。

## See also

- [`apidoc-llms-generated`](./apidoc-llms-generated.md) — 同じSSOT原理の知識面（`docs/llms.txt` 生成）。この型はその実行面
- [`alps-profile-ssot`](./alps-profile-ssot.md) — parameter説明の引用元になる意味のSSOT（ALPS profile）
- [`json-schema-validation`](./json-schema-validation.md) — schemaをResourceに結びつける `#[JsonSchema]`（tool定義の型情報源）
- [`cli-resource`](./cli-resource.md) — 同じ「コードを書き換えずResourceを別surfaceへ公開する」型（CLI公開）
- [`resource-permission-authorization`](./resource-permission-authorization.md) — write系resourceの公開を制御する認可gateの型
