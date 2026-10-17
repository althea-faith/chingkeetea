(function(){
var $=function(i){return document.getElementById(i)};
function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]})}
var bell=$("bell"),np=$("np");
if(bell){bell.onclick=function(){np.hidden=!np.hidden};document.addEventListener("click",function(e){if(!e.target.closest(".bw"))np.hidden=true})}
var qty=$("qty"),pv=$("pv"),cn=$("cn"),cur=null;
function preview(){if(!cur)return;var n=parseInt(qty&&qty.value,10)||0;
 if(pv)pv.innerHTML='<div>Current stock<b>'+cur.q+'</b>'+esc(cur.u)+'</div><div>Received<b>+'+n+'</b>'+esc(cur.u)+'</div><div>New stock<b>'+(cur.q+n)+'</b>'+esc(cur.u)+'</div>';
 if(cn)cn.textContent="Current stock: "+cur.q+" "+cur.u+(cur.q<=10?" · needs replenishment":"")}
document.querySelectorAll(".cb").forEach(function(cb){
 var items=JSON.parse(cb.dataset.items),inp=cb.querySelector(".cbi"),hid=cb.querySelector("input[type=hidden]"),ul=cb.querySelector("ul");
 function pick(it){hid.value=it.id;inp.value=it.n;ul.hidden=true;cur=it;preview()}
 function show(){var q=inp.value.toLowerCase(),h="";items.forEach(function(i){if((i.n+" "+i.c).toLowerCase().indexOf(q)<0)return;h+='<li data-id="'+i.id+'"><span>'+esc(i.n)+'</span><small>'+esc(i.s)+'</small></li>'});ul.innerHTML=h||'<li class="mu">No product found</li>';ul.hidden=false}
 inp.addEventListener("focus",show);inp.addEventListener("input",function(){hid.value="";cur=null;show()});
 inp.addEventListener("keydown",function(e){if(e.key==="Enter"){e.preventDefault();var f=ul.querySelector("li[data-id]");if(f)f.click()}});
 ul.addEventListener("click",function(e){var li=e.target.closest("li[data-id]");if(!li)return;items.forEach(function(i){if(i.id==li.dataset.id)pick(i)})});
 document.addEventListener("click",function(e){if(!cb.contains(e.target))ul.hidden=true});
 if(+cb.dataset.sel)items.forEach(function(i){if(i.id==cb.dataset.sel)pick(i)});
});
if(qty)qty.addEventListener("input",preview);
document.querySelectorAll("form[data-need]").forEach(function(f){f.addEventListener("submit",function(e){var h=f.querySelector("input[name=product_id]");if(h&&!h.value){e.preventDefault();alert("Select a product from the list.")}})});
})();
