Here are two wind-down routines: one for work nights and one for weekends.

```spec
{"op":"add","path":"/root","value":"routine"}
{"op":"add","path":"/state/ui","value":{"night":"weeknight","done":[]}}
{"op":"add","path":"/elements/routine","value":{"type":"Card","props":{"title":"Wind-down routine","icon":"sleep","tint":"sleep"},"children":["switch","weeknight","weekend","prep","log"]}}
{"op":"add","path":"/elements/switch","value":{"type":"Segmented","props":{"options":[{"label":"Weeknights","value":"weeknight"},{"label":"Weekends","value":"weekend"}],"value":{"$bindState":"/ui/night"}},"children":[]}}
{"op":"add","path":"/elements/weeknight","value":{"type":"Steps","props":{"items":[{"title":"Dim the lights","duration":"9:30 PM"},{"title":"Put your phone away","duration":"10:00 PM"},{"title":"Read a paper book","duration":"20 min"}]},"visible":{"$state":"/ui/night","eq":"weeknight"},"children":[]}}
{"op":"add","path":"/elements/weekend","value":{"type":"Steps","props":{"items":[{"title":"Start winding down by 10:30 PM","detail":"Stay within an hour of your weekday bedtime."},{"title":"Take a warm shower","duration":"10 min"},{"title":"Stretch gently","duration":"10 min"}]},"visible":{"$state":"/ui/night","eq":"weekend"},"children":[]}}
{"op":"add","path":"/elements/prep","value":{"type":"Checklist","props":{"items":[{"id":"blinds","label":"Close the blinds"},{"id":"alarm","label":"Set tomorrow's alarm"},{"id":"water","label":"Put water by the bed"}],"checked":{"$bindState":"/ui/done"}},"children":[]}}
{"op":"add","path":"/elements/log","value":{"type":"Button","props":{"label":"Log last night's sleep"},"on":{"press":{"action":"openDestination","params":{"destination":"log"}}},"children":[]}}
```

Keeping your bedtime within an hour on weekends helps your body clock stay steady.

```spec
{"op":"add","path":"/root","value":"next"}
{"op":"add","path":"/elements/next","value":{"type":"SuggestionChips","props":{"items":[{"label":"Make it shorter","action":{"action":"sendMessage","params":{"text":"Make the weeknight routine 15 minutes long."}}},{"label":"Snap my dinner","action":{"action":"openDestination","params":{"destination":"snap"}}},{"label":"Sleep hygiene guide","action":{"action":"openLink","params":{"url":"https://www.example.com/sleep-hygiene"}}}]},"children":[]}}
```
