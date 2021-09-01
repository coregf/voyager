"use strict";(self.webpackChunk=self.webpackChunk||[]).push([[6854],{7795:(e,l,o)=>{o.r(l),o.d(l,{default:()=>pe});var t=o(6252),n=o(3577),a=o(9963);const i={class:"voyager-table striped mt-0"},s=(0,t._)("th",{class:"hidden md:table-cell"},null,-1),u={class:"hidden md:table-cell"},d={class:"hidden md:table-cell"},r={class:"hidden md:table-cell"},c={class:"hidden md:table-cell"},_={class:"hidden md:table-cell"},p={class:"hidden md:table-cell"},m={class:"flex justify-end"},g={class:"hidden md:table-cell dd-handle cursor-move"},y={class:"h-5 w-5"},b={class:"hidden md:table-cell"},w=["onUpdate:modelValue"],v=["label"],h=["value"],f=["label"],k=["value"],z=["label"],V=["value"],U={class:"inline-flex items-center space-x-1"},x=["onUpdate:modelValue","disabled"],D={value:null},W={value:"edit"},C={value:"read"},F={class:"hidden md:table-cell"},$=["onUpdate:modelValue"],B={class:"hidden md:table-cell"},j=["onUpdate:modelValue","disabled"],q={class:"hidden md:table-cell"},H=["disabled","checked","value"],M={class:"hidden md:table-cell"},T=["onUpdate:modelValue","disabled"],Y={class:"flex flex-no-wrap space-x-1 justify-end"},K={class:"input-group mt-2"},S={class:"label mt-4"},I=["onUpdate:modelValue"],L={class:"input-group mt-2"},P={class:"label"},R=["onUpdate:modelValue"],O={class:"button"},J=["onClick"],N={class:"voyager-table"},A={class:"flex justify-end"},G=["onUpdate:modelValue"],Q={value:null},E=["onUpdate:modelValue","disabled"],X={value:"="},Z={value:"!="},ee={value:">="},le={value:">"},oe={value:"<="},te={value:"<"},ne={value:"like"},ae=["onUpdate:modelValue"],ie={class:"w-full"},se={class:"button"},ue=["onClick"],de={class:"flex justify-end"},re=["onClick"];var ce=o(9980);const _e={components:{draggable:o.n(ce)()},emits:["update:formfields","update:options","delete"],props:["computed","columns","relationships","formfields","options"],data(){return{colors:this.colors}},methods:{addFilter(){this.$refs.filters_collapsible.open();var e=this.options;this.isArray(e.filters)||(e.filters=[]),e.filters.push({name:"",column:null,operator:"=",value:"",color:"accent",icon:null}),this.$emit("update:options",e)},removeFilter(e){var l=this.options;l.filters.splice(e,1),this.$emit("update:options",l)},getRelatedBread(e){if("relationship"==e.type){let l=e.column.split(".")[0],o=this.relationships.where("method",l).first();if(o&&o.hasOwnProperty("bread")&&o.bread)return o.bread}return null}},render:function(e,l,o,ce,_e,pe){const me=(0,t.up)("Icon"),ge=(0,t.up)("LanguageInput"),ye=(0,t.up)("LocalePicker"),be=(0,t.up)("SlideIn"),we=(0,t.up)("draggable"),ve=(0,t.up)("ColorPicker"),he=(0,t.up)("IconPicker"),fe=(0,t.up)("Modal"),ke=(0,t.up)("Collapsible"),ze=(0,t.Q2)("tooltip");return(0,t.wg)(),(0,t.iD)("div",null,[(0,t._)("div",i,[(0,t._)("table",null,[(0,t._)("thead",null,[(0,t._)("tr",null,[s,(0,t._)("th",u,(0,n.zw)(e.__("voyager::generic.type")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.column")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.title")),1),(0,t._)("th",d,(0,n.zw)(e.__("voyager::builder.link_to")),1),(0,t._)("th",r,(0,n.zw)(e.__("voyager::builder.searchable")),1),(0,t._)("th",c,(0,n.zw)(e.__("voyager::builder.orderable")),1),(0,t._)("th",_,(0,n.zw)(e.__("voyager::builder.order_default")),1),(0,t._)("th",p,(0,n.zw)(e.__("voyager::generic.translatable")),1),(0,t._)("th",m,(0,n.zw)(e.__("voyager::generic.actions")),1)])]),(0,t.Wm)(we,{tag:"tbody",modelValue:o.formfields,"onUpdate:modelValue":l[1]||(l[1]=l=>e.$emit("update:formfields",JSON.parse(JSON.stringify(l)))),"item-key":""},{item:(0,t.w5)((({element:i,index:s})=>[(0,t._)("tr",null,[(0,t.wy)((0,t._)("td",g,[(0,t._)("div",y,[(0,t.Wm)(me,{icon:"selector"})])],512),[[ze,e.__("voyager::generic.move")]]),(0,t._)("td",b,(0,n.zw)(e.getFormfieldByType(i.type).name),1),(0,t._)("td",null,[(0,t.wy)((0,t._)("select",{class:"input small w-full","onUpdate:modelValue":e=>i.column=e},[e.getFormfieldByType(i.type).allow_columns?((0,t.wg)(),(0,t.iD)("optgroup",{key:0,label:e.__("voyager::builder.columns")},[((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(o.columns,((e,l)=>((0,t.wg)(),(0,t.iD)("option",{key:"column_"+l,value:{column:e,type:"column"}},(0,n.zw)(e),9,h)))),128))],8,v)):(0,t.kq)("v-if",!0),e.getFormfieldByType(i.type).allow_computed_props&&o.computed.length>0?((0,t.wg)(),(0,t.iD)("optgroup",{key:1,label:e.__("voyager::builder.computed")},[((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(o.computed,((e,l)=>((0,t.wg)(),(0,t.iD)("option",{key:"computed_"+l,value:{column:e,type:"computed"}},(0,n.zw)(e),9,k)))),128))],8,f)):(0,t.kq)("v-if",!0),((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(o.relationships,((l,o)=>((0,t.wg)(),(0,t.iD)(t.HY,{key:"relationship_"+o},[e.getFormfieldByType(i.type).allow_relationship_props?((0,t.wg)(),(0,t.iD)("optgroup",{key:0,label:l.method},[((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(l.columns,((e,o)=>((0,t.wg)(),(0,t.iD)("option",{key:"column_"+o,value:{column:l.method+"."+e,type:"relationship"}},(0,n.zw)(l.method+"."+e),9,V)))),128))],8,z)):(0,t.kq)("v-if",!0)],64)))),128))],8,w),[[a.bM,i.column]])]),(0,t._)("td",null,[(0,t.Wm)(ge,{class:"input small w-full",type:"text",placeholder:"Title",modelValue:i.title,"onUpdate:modelValue":e=>i.title=e},null,8,["modelValue","onUpdate:modelValue"])]),(0,t._)("td",U,[(0,t.wy)((0,t._)("select",{class:"input small w-full","onUpdate:modelValue":e=>i.link_to=e,disabled:null===pe.getRelatedBread(i.column)&&"relationship"==i.column.type},[(0,t._)("option",D,(0,n.zw)(e.__("voyager::generic.nothing")),1),(0,t._)("option",W,(0,n.zw)(e.__("voyager::generic.edit")),1),(0,t._)("option",C,(0,n.zw)(e.__("voyager::generic.read")),1)],8,x),[[a.bM,i.link_to]]),null!==pe.getRelatedBread(i.column)?(0,t.wy)(((0,t.wg)(),(0,t.j4)(me,{key:0,icon:"information-circle",size:6},null,512)),[[ze,e.__("voyager::builder.links_to_bread")]]):(0,t.kq)("v-if",!0),null===pe.getRelatedBread(i.column)&&"relationship"==i.column.type?(0,t.wy)(((0,t.wg)(),(0,t.j4)(me,{key:1,icon:"information-circle",size:6,class:"text-red-500"},null,512)),[[ze,e.__("voyager::builder.cannot_link")]]):(0,t.kq)("v-if",!0)]),(0,t._)("td",F,[(0,t.wy)((0,t._)("input",{class:"input",type:"checkbox","onUpdate:modelValue":e=>i.searchable=e},null,8,$),[[a.e8,i.searchable]])]),(0,t._)("td",B,[(0,t.wy)((0,t._)("input",{class:"input",type:"checkbox","onUpdate:modelValue":e=>i.orderable=e,disabled:"column"!==i.column.type},null,8,j),[[a.e8,i.orderable]])]),(0,t._)("td",q,[(0,t.wy)((0,t._)("input",{class:"input",type:"radio",disabled:"column"!==i.column.type,checked:o.options.default_order_column&&o.options.default_order_column==i.column,"onUpdate:modelValue":l[0]||(l[0]=e=>o.options.default_order_column=e),value:i.column},null,8,H),[[a.G2,o.options.default_order_column]])]),(0,t._)("td",M,[(0,t.wy)((0,t._)("input",{type:"checkbox",class:"input","onUpdate:modelValue":e=>i.translatable=e,disabled:!e.getFormfieldByType(i.type).can_be_translated},null,8,T),[[a.e8,i.translatable]])]),(0,t._)("td",Y,[(0,t.Wm)(be,{title:e.__("voyager::generic.options")},{actions:(0,t.w5)((()=>[(0,t.Wm)(ye)])),opener:(0,t.w5)((()=>[(0,t._)("button",O,[(0,t.Wm)(me,{icon:"cog"}),(0,t._)("span",null,(0,n.zw)(e.__("voyager::generic.options")),1)])])),default:(0,t.w5)((()=>[((0,t.wg)(),(0,t.j4)((0,t.LL)(e.getFormfieldByType(i.type).builder_component),{orgoptions:i.options,"onUpdate:orgoptions":e=>i.options=e,column:i.column,columns:o.columns,action:"list-options"},null,8,["orgoptions","onUpdate:orgoptions","column","columns"])),(0,t._)("div",K,[(0,t._)("label",S,(0,n.zw)(e.__("voyager::generic.component")),1),(0,t.wy)((0,t._)("input",{type:"text",class:"input w-full","onUpdate:modelValue":e=>i.component=e},null,8,I),[[a.nr,i.component]])]),(0,t._)("div",L,[(0,t._)("label",P,(0,n.zw)(e.__("voyager::generic.classes")),1),(0,t.wy)((0,t._)("input",{type:"text",class:"input w-full","onUpdate:modelValue":e=>i.options.classes=e},null,8,R),[[a.nr,i.options.classes]])])])),_:2},1032,["title"]),(0,t._)("button",{class:"button",onClick:l=>e.$emit("delete",s)},[(0,t.Wm)(me,{icon:"trash",class:"text-red-500"}),(0,t._)("span",null,(0,n.zw)(e.__("voyager::generic.delete")),1)],8,J)])])])),_:1},8,["modelValue"])])]),(0,t.Wm)(ke,{title:`${e.__("voyager::generic.filters")} (${o.options.filters.length||0})`,closed:"",ref:"filters_collapsible"},{actions:(0,t.w5)((()=>[(0,t._)("button",{class:"button small",onClick:l[2]||(l[2]=(0,a.iM)(((...e)=>pe.addFilter&&pe.addFilter(...e)),["stop"]))},[(0,t.Wm)(me,{icon:"plus",class:"text-green-500"})])])),default:(0,t.w5)((()=>[(0,t._)("div",N,[(0,t._)("table",null,[(0,t._)("thead",null,[(0,t._)("tr",null,[(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.name")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.column")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.operator")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::builder.value_or_scope")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.color")),1),(0,t._)("th",null,(0,n.zw)(e.__("voyager::generic.icon")),1),(0,t._)("th",A,(0,n.zw)(e.__("voyager::generic.actions")),1)])]),(0,t._)("tbody",null,[((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(o.options.filters,((l,i)=>((0,t.wg)(),(0,t.iD)("tr",{key:"filter-"+i},[(0,t._)("td",null,[(0,t.Wm)(ge,{class:"input small w-full",type:"text",placeholder:e.__("voyager::generic.name"),modelValue:l.name,"onUpdate:modelValue":e=>l.name=e},null,8,["placeholder","modelValue","onUpdate:modelValue"])]),(0,t._)("td",null,[(0,t.wy)((0,t._)("select",{class:"input small w-full","onUpdate:modelValue":e=>l.column=e},[(0,t._)("option",Q,(0,n.zw)(e.__("voyager::generic.none")),1),((0,t.wg)(!0),(0,t.iD)(t.HY,null,(0,t.Ko)(o.columns,(e=>((0,t.wg)(),(0,t.iD)("option",{key:e},(0,n.zw)(e),1)))),128))],8,G),[[a.bM,l.column]])]),(0,t._)("td",null,[(0,t.wy)((0,t._)("select",{class:"input small w-full","onUpdate:modelValue":e=>l.operator=e,disabled:null===l.column},[(0,t._)("option",X,(0,n.zw)(e.__("voyager::builder.operators.equals")),1),(0,t._)("option",Z,(0,n.zw)(e.__("voyager::builder.operators.not_equals")),1),(0,t._)("option",ee,(0,n.zw)(e.__("voyager::builder.operators.bigger_than")),1),(0,t._)("option",le,(0,n.zw)(e.__("voyager::builder.operators.bigger")),1),(0,t._)("option",oe,(0,n.zw)(e.__("voyager::builder.operators.smaller_than")),1),(0,t._)("option",te,(0,n.zw)(e.__("voyager::builder.operators.smaller")),1),(0,t._)("option",ne,(0,n.zw)(e.__("voyager::builder.operators.like")),1)],8,E),[[a.bM,l.operator]])]),(0,t._)("td",null,[(0,t.wy)((0,t._)("input",{type:"text",class:"input small w-full","onUpdate:modelValue":e=>l.value=e},null,8,ae),[[a.nr,l.value]])]),(0,t._)("td",null,[(0,t.Wm)(ve,{modelValue:l.color,"onUpdate:modelValue":e=>l.color=e,size:2,"add-none":""},null,8,["modelValue","onUpdate:modelValue"])]),(0,t._)("td",null,[(0,t.Wm)(fe,{ref:`filter_icon_modal_${i}`,title:e.__("voyager::generic.select_icon")},{opener:(0,t.w5)((()=>[(0,t._)("div",ie,[(0,t._)("button",se,[(0,t.Wm)(me,{class:"my-1 content-center",icon:l.icon?l.icon:"ban",key:i+(l.icon?l.icon:"ban")},null,8,["icon"])])])])),actions:(0,t.w5)((()=>[(0,t._)("button",{class:"button",onClick:o=>{l.icon=null,e.$refs["filter_icon_modal_"+i].close()}},(0,n.zw)(e.__("voyager::generic.none")),9,ue)])),default:(0,t.w5)((()=>[(0,t.Wm)(he,{onSelect:o=>{l.icon=o,e.$refs[`filter_icon_modal_${i}`].close()}},null,8,["onSelect"])])),_:2},1032,["title"])]),(0,t._)("td",de,[(0,t._)("button",{class:"button small",onClick:(0,a.iM)((e=>pe.removeFilter(i)),["stop"])},[(0,t.Wm)(me,{icon:"trash",class:"text-red-500"})],8,re)])])))),128))])])])])),_:1},8,["title"])])}},pe=_e}}]);