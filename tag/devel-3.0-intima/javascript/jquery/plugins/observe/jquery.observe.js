(function($){$.fn.observe=function(options,callback){var self=this;self.getValue=function(){var val=[];self.nodes.each(function(){val.push($(this).val())});return val.join('|')};self.start=function(){self.stop();self.lastTrigger=self.lastChange=new Date();self.interval=window.setInterval(self.intervalCheck,self.options.checkDelay);self.lastValue=self.getValue();self.changed=false};self.stop=function(){window.clearTimeout(self.timeout);window.clearInterval(self.interval)};self.intervalCheck=function(){if(self.nodes.parents('body').length==0){self.stop();return};if(self.options.type!='constant'||!self.changed){var val=self.getValue();if(val!=self.lastValue){self.lastChange=new Date();self.lastValue=val;if(!self.changed){self.changed=true;self.lastTrigger=new Date();self.lastTrigger.setMilliseconds(0-self.options.checkDelay);return}}};if(!self.changed)return;var now=new Date(),trigger=false;switch(self.options.type){case'once':if(now-self.lastChange>self.options.delay)trigger=true;break;case'constant':if(now-self.lastTrigger>self.options.delay)trigger=true;break;default:throw new Error('jQuery.observe: "'+self.options.type+'" is not a valid observer type');break};if(!trigger)return;self.lastTrigger=new Date();self.changed=false;self.options.callback()};if(typeof callback=='function'){options.callback=callback;delete callback}else if(typeof options=='function')var options={callback:options};self.options=$.extend({type:'once',delay:1000,checkDelay:100,callback:function(){},start:true},options);self.nodes=$(this);self.timeout=null;self.interval=null;if(self.options.start)self.start();return $(this)}})(jQuery)