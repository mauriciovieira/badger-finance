
Array.prototype.toJSONString=function(){var a=['['],b,i,l=this.length,v;for(i=0;i<l;i+=1){v=this[i];switch(typeof v){case'undefined':case'function':case'unknown':break;default:if(b){a.push(',');}
a.push(v===null?"null":v.toJSONString());b=true;}}
a.push(']');return a.join('');};Boolean.prototype.toJSONString=function(){return String(this);};Date.prototype.toJSONString=function(){function f(n){return n<10?'0'+n:n;}
return'"'+this.getFullYear()+'-'+
f(this.getMonth()+1)+'-'+
f(this.getDate())+'T'+
f(this.getHours())+':'+
f(this.getMinutes())+':'+
f(this.getSeconds())+'"';};Number.prototype.toJSONString=function(){return isFinite(this)?String(this):"null";};Object.prototype.toJSONString=function(){var a=['{'],b,i,v;for(i in this){if(this.hasOwnProperty(i)){v=this[i];switch(typeof v){case'undefined':case'function':case'unknown':break;default:if(b){a.push(',');}
a.push(i.toJSONString(),':',v===null?"null":v.toJSONString());b=true;}}}
a.push('}');return a.join('');};String.prototype.parseJSON=function(){try{if(/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/.test(this)){return eval('('+this+')');}}catch(e){}
throw new SyntaxError("parseJSON");};(function(){var m={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};String.prototype.toJSONString=function(){if(/["\\\x00-\x1f]/.test(this)){return'"'+this.replace(/([\x00-\x1f\\"])/g,function(a,b){var c=m[b];if(c){return c;}
c=b.charCodeAt();return'\\u00'+
Math.floor(c/16).toString(16)+
(c%16).toString(16);})+'"';}
return'"'+this+'"';};})();