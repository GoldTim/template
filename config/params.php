<?php
return [
    'status'        =>['否','是'],
    'sendType'      =>['快递', 'EMS', '平邮'],
    'shipType'      =>['件', '重量', '体积'],
    'termType'      =>['数字', '金额', '合并'],
    'sendTime'      =>['4小时内', '8小时内'],
    'productStatus' =>['上架','仓库','下架','已售罄'],
    'couponStatus'  =>['待使用','已使用','已失效'],
    'orderStatus'   =>['未支付','已支付','已发货','已完成','仅退款','退款退货','取消订单','订单超时'],
    'orderAfterType'=>['退款','退款退货','换货'],
    'payMethod'     =>['weChatNative','WeChatApp','weChatMweb','weChatJsApi','weChatProcedure','aliPayApp','aliPayNative']
];
