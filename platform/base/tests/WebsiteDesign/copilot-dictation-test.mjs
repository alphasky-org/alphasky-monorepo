import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const instances = [];
class Recognizer {
    constructor() { instances.push(this); }
    start() {}
    stop() { this.onend(); }
    abort() { this.onend(); }
    emit(...phrases) {
        this.onresult({results: phrases.map(transcript => [{transcript}])});
    }
}
const input = Object.assign(new EventTarget(), {value:'ملاحظة:', focus() {}});
const button = Object.assign(new EventTarget(), {classList:{add(){},remove(){}}, setAttribute(){}});
const bind = vm.runInNewContext(readFileSync(new URL('../../resources/views/partials/copilot-dictation.blade.php',import.meta.url),'utf8')+'\nbindCopilotDictation', {window:{SpeechRecognition:Recognizer}, Event});
const cancel = bind(input,button,()=>assert.fail('Unexpected unsupported state'));
const click = () => button.dispatchEvent(new Event('click'));
click();
const first=instances.at(-1);
first.emit('أضف');
first.emit('أضف العنوان');
first.emit('أضف العنوان','والتـ');
first.emit('أضف العنوان','والتلفون');
assert.equal(input.value,'ملاحظة: أضف العنوان والتلفون');
first.onend();
click();
instances.at(-1).emit('في الفوتر');
assert.equal(input.value,'ملاحظة: أضف العنوان والتلفون في الفوتر');
input.dispatchEvent(new Event('beforeinput'));
input.value += ' اليوم';
instances.at(-1).emit('تحديث متأخر');
assert.ok(input.value.endsWith('اليوم'));
click();
instances.at(-1).emit('please');
click();
click();
instances.at(-1).emit('confirm');
cancel();
input.value = '';
instances.at(-1).emit('late result after send');
assert.equal(input.value,'');
console.log('Passed Copilot cumulative, interim, restart, edit and late-result checks.');
