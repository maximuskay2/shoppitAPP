enum ActiveOrderStage {
  request,
  pickup,
  outForDelivery,
  deliver,
}

class ActiveOrderState {
  const ActiveOrderState({
    required this.orderId,
    required this.stage,
  });

  final String orderId;
  final ActiveOrderStage stage;
}
