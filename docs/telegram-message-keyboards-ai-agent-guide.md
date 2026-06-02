# Telegram Message Keyboards guideline

This guide explains how to use Telegraph message keyboards as the action surface for a Laravel AI agent.

Telegraph message keyboards are Telegram inline keyboards attached to bot messages. For an AI agent, they are best used as a controlled bridge between model-generated intent and Laravel-owned execution. The agent may propose an action, but Laravel should decide which buttons are available, which callback action is allowed, and which server-side record the button references.

## Mental Model

Use keyboards when the user or operator needs to choose from a bounded set of actions:

- Approve or reject an agent tool call.
- Pick one of several model-suggested next steps.
- Open a review page.
- Copy a generated token, invite link, or reference code.
- Launch a Telegram Web App for richer interaction.

Do not put raw LLM output, sensitive data, or full tool parameters into callback payloads. Store the real data in your database and pass compact identifiers such as `approval_id`, `run_id`, or `message_id`.

## Send a Message With a Keyboard

```php
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

Telegraph::message('The agent wants to process this refund. Approve?')
    ->keyboard(Keyboard::make()->buttons([
        Button::make('Approve')->action('approveToolCall')->param('approval_id', '42'),
        Button::make('Reject')->action('rejectToolCall')->param('approval_id', '42'),
        Button::make('Open request')->url('https://app.test/approvals/42'),
    ]))
    ->send();
```

The `action()` value maps to a public method on your custom Telegraph webhook handler. The `param()` values are delivered as callback data.

## Build a Keyboard With a Closure

Closure syntax is useful when the keyboard is assembled dynamically.

```php
use DefStudio\Telegraph\Keyboard\Keyboard;

Telegraph::message('Choose the next action')
    ->keyboard(function (Keyboard $keyboard): Keyboard {
        return $keyboard
            ->button('Approve')->action('approveToolCall')->param('id', '42')
            ->button('Reject')->action('rejectToolCall')->param('id', '42')
            ->button('View')->url('https://app.test/agent-runs/42');
    })
    ->send();
```

## Button Types

### Callback Buttons

Use callback buttons for application actions that Laravel must handle.

```php
Button::make('Approve')->action('approveToolCall')->param('id', '42');
```

Good uses:

- Approving a pending AI tool call.
- Rejecting a proposed action.
- Retrying an agent run.
- Marking a notification or task as resolved.

### URL Buttons

Use URL buttons for navigation.

```php
Button::make('Open dashboard')->url('https://app.test/dashboard');
```

Good uses:

- Opening a review screen.
- Linking to a customer profile.
- Sending the user to documentation or a billing page.

### Web App Buttons

Use Web App buttons when the interaction needs a richer Telegram Web App.

```php
Button::make('Open review app')->webApp('https://app.test/telegram/review');
```

Good uses:

- Multi-field review flows.
- Human-in-the-loop approval screens.
- Workflows that are too complex for a few inline buttons.

### Login URL Buttons

Use Login URL buttons for Telegram login widget flows.

```php
Button::make('Login')->loginUrl('https://app.test/auth/telegram');
```

### Switch Inline Query Buttons

Use inline query buttons when users should insert a bot query into another chat or the current chat.

```php
Button::make('Share result')->switchInlineQuery('agent-summary-42');
Button::make('Use here')->switchInlineQuery('agent-summary-42')->currentChat();
```

### Copy Text Buttons

Use copy buttons for values users need to paste somewhere else.

```php
Button::make('Copy invite link')->copyText('https://t.me/joinchat/ABC123');
Button::make('Copy promo code')->copyText('SAVE20OFF');
Button::make('Copy support email')->copyText('support@example.com');
```

## Layout

Telegraph normally places one button per row. Approval flows are easier to scan when competing actions sit on the same row.

```php
$keyboard = Keyboard::make()
    ->row([
        Button::make('Approve')->action('approveToolCall')->param('id', '42'),
        Button::make('Reject')->action('rejectToolCall')->param('id', '42'),
    ])
    ->row([
        Button::make('Open details')->url('https://app.test/approvals/42'),
    ]);
```

You can also use button widths:

```php
$keyboard = Keyboard::make()
    ->button('Approve')->action('approveToolCall')->param('id', '42')->width(0.5)
    ->button('Reject')->action('rejectToolCall')->param('id', '42')->width(0.5)
    ->button('Details')->url('https://app.test/approvals/42');
```

For repeated options, use `chunk()`:

```php
$keyboard = Keyboard::make()
    ->button('A')->action('chooseOption')->param('value', 'a')
    ->button('B')->action('chooseOption')->param('value', 'b')
    ->button('C')->action('chooseOption')->param('value', 'c')
    ->chunk(2);
```

## Conditional Buttons

Use `when()` to show buttons only when the current user or operator is allowed to perform the action.

```php
Keyboard::make()
    ->button('Dismiss')->action('dismissToolCall')->param('id', '42')->width(0.5)
    ->when(
        $userCanApprove,
        fn (Keyboard $keyboard): Keyboard => $keyboard
            ->button('Approve')->action('approveToolCall')->param('id', '42')->width(0.5)
    );
```

Authorization still belongs on the server. Conditional buttons improve the UI, but webhook handlers must re-check permissions.

## Handle Callback Buttons

Callback buttons are handled by public methods on a Telegraph webhook handler.

```php
namespace App\Http\Webhooks;

use App\Actions\ApproveAgentToolCall;
use App\Actions\RejectAgentToolCall;
use DefStudio\Telegraph\Handlers\WebhookHandler;

final class AgentWebhookHandler extends WebhookHandler
{
    public function approveToolCall(ApproveAgentToolCall $approveAgentToolCall): void
    {
        $approvalId = (int) $this->data->get('id');

        $approveAgentToolCall->handle($approvalId);

        $this->reply('Approved. The agent action is queued.');
        $this->deleteKeyboard();
    }

    public function rejectToolCall(RejectAgentToolCall $rejectAgentToolCall): void
    {
        $approvalId = (int) $this->data->get('id');

        $rejectAgentToolCall->handle($approvalId);

        $this->reply('Rejected.');
        $this->deleteKeyboard();
    }
}
```

Register the handler in `config/telegraph.php`:

```php
'webhook' => [
    'handler' => App\Http\Webhooks\AgentWebhookHandler::class,
],
```

## Replace or Delete a Keyboard

After a callback is handled, remove or replace the keyboard so stale buttons cannot be clicked again.

```php
$this->deleteKeyboard();
```

Or replace it with a reduced keyboard:

```php
$newKeyboard = $this->originalKeyboard->deleteButton('Approve');

$this->replaceKeyboard($newKeyboard);
```

You can also update a keyboard by message ID:

```php
Telegraph::replaceKeyboard(
    messageId: 1568794,
    newKeyboard: Keyboard::make()->buttons([
        Button::make('Open details')->url('https://app.test/approvals/42'),
    ]),
)->send();
```

To remove a keyboard by message ID:

```php
Telegraph::deleteKeyboard(messageId: 1568794)->send();
```

## AI Agent Approval Pattern

Use this pattern for high-risk tool calls:

1. The AI agent proposes an action.
2. Laravel validates the proposed action and parameters.
3. Laravel creates a pending approval record.
4. Telegraph sends an operator message with approval buttons.
5. The operator clicks approve or reject.
6. The webhook handler loads the pending approval record.
7. Laravel re-authorizes the action.
8. Laravel marks the record resolved and dispatches execution through an Action or queued job.
9. The webhook replies to Telegram and deletes or replaces the keyboard.

The important boundary is that the Telegram button approves a server-side record. It should not execute arbitrary model text or trust callback payloads as the source of truth.

## Production Rules

- Keep callback payloads small and non-sensitive.
- Use stable callback action names such as `approveToolCall`, `rejectToolCall`, and `retryAgentRun`.
- Store full agent tool parameters in your database.
- Validate and authorize again inside the webhook handler.
- Make approval and execution idempotent.
- Delete or replace keyboards after successful callbacks.
- Use queued jobs for slow or external side effects.
- Log approval decisions for auditability.
- Prefer URL or Web App buttons for complex review workflows.

## References

- Telegraph Message Keyboards: https://docs.defstudio.it/telegraph/v1/features/keyboards
- Telegraph Callback Data: https://docs.defstudio.it/telegraph/v1/webhooks/callback-data
- Telegraph Webhook Request Types: https://docs.defstudio.it/telegraph/v1/webhooks/webhook-request-types
- Telegraph Keyboard Interaction: https://docs.defstudio.it/telegraph/v1/webhooks/keyboard-interaction
