package com.shoppitplus.shoppit.models


import com.shoppitplus.shoppit.utils.AddAddressRequest
import com.shoppitplus.shoppit.utils.AddAddressResponse
import com.shoppitplus.shoppit.utils.AddToCartRequest
import com.shoppitplus.shoppit.utils.AddToCartResponse
import com.shoppitplus.shoppit.utils.ApiResponse
import com.shoppitplus.shoppit.utils.ApiResponses
import com.shoppitplus.shoppit.utils.BaseResponse
import com.shoppitplus.shoppit.utils.CartResponse
import com.shoppitplus.shoppit.utils.ClearCartResponse
import com.shoppitplus.shoppit.utils.ClearVendorCartResponse
import com.shoppitplus.shoppit.utils.CouponsListResponse
import com.shoppitplus.shoppit.utils.CreateCategoryResponse
import com.shoppitplus.shoppit.utils.CreatePasswordRequest
import com.shoppitplus.shoppit.utils.CreatePasswordResponse
import com.shoppitplus.shoppit.utils.DeliveryZoneCheckResponse
import com.shoppitplus.shoppit.utils.CreateProductResponse
import com.shoppitplus.shoppit.utils.DeleteCartItemResponse
import com.shoppitplus.shoppit.utils.DeleteProductResponse
import com.shoppitplus.shoppit.utils.DepositRequest
import com.shoppitplus.shoppit.utils.DepositResponse
import com.shoppitplus.shoppit.utils.DriverAuthResponse
import com.shoppitplus.shoppit.utils.DriverEarningsResponse
import com.shoppitplus.shoppit.utils.DriverLocationUpdateRequest
import com.shoppitplus.shoppit.utils.DriverLoginRequest
import com.shoppitplus.shoppit.utils.DriverNavigationRequest
import com.shoppitplus.shoppit.utils.DriverOrderResponse
import com.shoppitplus.shoppit.utils.DriverOrdersResponse
import com.shoppitplus.shoppit.utils.DriverOtpRequest
import com.shoppitplus.shoppit.utils.DriverPayoutRequest
import com.shoppitplus.shoppit.utils.DriverPayoutResponse
import com.shoppitplus.shoppit.utils.DriverProfileResponse
import com.shoppitplus.shoppit.utils.DriverRegisterRequest
import com.shoppitplus.shoppit.utils.DriverRejectRequest
import com.shoppitplus.shoppit.utils.DriverStatusRequest
import com.shoppitplus.shoppit.utils.DriverStatsResponse
import com.shoppitplus.shoppit.utils.DriverSupportRequest
import com.shoppitplus.shoppit.utils.DriverSupportResponse
import com.shoppitplus.shoppit.utils.DriverVehiclesResponse
import com.shoppitplus.shoppit.utils.GenericResponse
import com.shoppitplus.shoppit.utils.LoginRequest
import com.shoppitplus.shoppit.utils.LoginResponse
import com.shoppitplus.shoppit.utils.MarkReadResponse
import com.shoppitplus.shoppit.utils.NearbyProductsResponse
import com.shoppitplus.shoppit.utils.NearbyVendorsResponse
import com.shoppitplus.shoppit.utils.NotificationResponse
import com.shoppitplus.shoppit.utils.Order
import com.shoppitplus.shoppit.utils.OrderEtaResponse
import com.shoppitplus.shoppit.utils.OrderResponse
import com.shoppitplus.shoppit.utils.OrderTrackingResponse
import com.shoppitplus.shoppit.utils.OrdersResponse
import com.shoppitplus.shoppit.utils.ReviewRequest
import com.shoppitplus.shoppit.utils.ReviewResponse
import com.shoppitplus.shoppit.utils.StoreHoursRequest
import com.shoppitplus.shoppit.utils.StoreHoursResponse
import com.shoppitplus.shoppit.utils.VendorPayoutRequest
import com.shoppitplus.shoppit.utils.VendorPayoutRequestResponse
import com.shoppitplus.shoppit.utils.VendorPayoutsResponse
import com.shoppitplus.shoppit.utils.PaginatedResponse
import com.shoppitplus.shoppit.utils.ProcessCartRequest
import com.shoppitplus.shoppit.utils.ProcessCartResponse
import com.shoppitplus.shoppit.utils.ProductCategoryResponse
import com.shoppitplus.shoppit.utils.ProductDto
import com.shoppitplus.shoppit.utils.ProductResponse
import com.shoppitplus.shoppit.utils.RegistrationRequest
import com.shoppitplus.shoppit.utils.RegistrationResponse
import com.shoppitplus.shoppit.utils.RefundStatusResponse
import com.shoppitplus.shoppit.utils.ResendOtpRequest
import com.shoppitplus.shoppit.utils.SetupProfileRequest
import com.shoppitplus.shoppit.utils.SetupProfileResponse
import com.shoppitplus.shoppit.utils.SingleNotificationResponse
import com.shoppitplus.shoppit.utils.StatsResponse
import com.shoppitplus.shoppit.utils.ToggleAvailabilityRequest
import com.shoppitplus.shoppit.utils.UnreadCountResponse
import com.shoppitplus.shoppit.utils.UpdateCartItemRequest
import com.shoppitplus.shoppit.utils.UpdateProductResponse
import com.shoppitplus.shoppit.utils.UpdateProfileResponse
import com.shoppitplus.shoppit.utils.UserResponse
import com.shoppitplus.shoppit.utils.VendorCartResponse
import com.shoppitplus.shoppit.utils.VendorDto
import com.shoppitplus.shoppit.utils.VendorProductsResponse
import com.shoppitplus.shoppit.utils.VendorResponse
import com.shoppitplus.shoppit.utils.VendorSubscriptionResponse
import com.shoppitplus.shoppit.utils.VerifyOtpRequest
import com.shoppitplus.shoppit.utils.VerifyOtpResponse
import com.shoppitplus.shoppit.utils.WaitlistResponse
import com.shoppitplus.shoppit.utils.WalletBalanceResponse
import com.shoppitplus.shoppit.utils.WalletTransactionsResponse
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Call
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.Query

interface Api {
    // Unified Notification Endpoints
    @GET("notifications/unified")
    suspend fun getUnifiedNotifications(@Query("page") page: Int = 1): NotificationResponse

    @POST("notifications/unified/{id}/read")
    suspend fun markUnifiedNotificationRead(@Path("id") id: String): MarkReadResponse

    @POST("notifications/unified/{id}/unread")
    suspend fun markUnifiedNotificationUnread(@Path("id") id: String): MarkReadResponse

    @POST("notifications/unified/send")
    suspend fun sendUnifiedNotification(@Body request: Map<String, Any>): MarkReadResponse


    @GET("delivery-zones/check")
    suspend fun checkDeliveryZone(
        @Query("latitude") latitude: Double,
        @Query("longitude") longitude: Double
    ): Response<DeliveryZoneCheckResponse>

    @POST("auth/register")
    suspend fun register(@Body request: RegistrationRequest): Response<RegistrationResponse>


    @POST("auth/verify-register-otp")
    suspend fun verifyRegisterOtp(
        @Body request: VerifyOtpRequest
    ): VerifyOtpResponse

    @POST("auth/resend-register-otp")
    suspend fun resendRegisterOtp(
        @Body request: ResendOtpRequest
    ): VerifyOtpResponse

    @POST("auth/send-code")
    suspend fun sendResetCode(
        @Body request: Map<String, String>
    ): Response<BaseResponse>

    @POST("auth/verify-code")
    suspend fun verifyResetCode(
        @Body request: Map<String, String>
    ): Response<BaseResponse>

    @POST("auth/reset-password")
    suspend fun resetPassword(
        @Body request: Map<String, String>
    ): Response<BaseResponse>

    @POST("user/account/setup-profile")
    suspend fun setupProfile(
        @Body request: SetupProfileRequest
    ): SetupProfileResponse

    @POST("user/account/create-password")
    suspend fun createPassword(
        @Body request: CreatePasswordRequest
    ): CreatePasswordResponse

    @POST("auth/login")
    suspend fun login(
        @Body request: LoginRequest
    ): LoginResponse

    @GET("user/account")
    suspend fun getUserAccount(): UserResponse

    @GET("user/discovery/products/nearby")
    suspend fun getNewProducts(): NearbyProductsResponse

    @Multipart
    @POST("user/account/setup-vendor-profile")
    suspend fun setupVendorProfile(
        @Part("full_name") fullName: RequestBody,
        @Part("tin") tin: RequestBody,
        @Part("phone") phone: RequestBody,
        @Part("business_name") businessName: RequestBody,
        @Part("state") state: RequestBody,
        @Part("city") city: RequestBody,
        @Part("address") address: RequestBody,
        @Part("cac") cac: RequestBody,
    ): RegistrationResponse


    @POST("user/cart/add")
    suspend fun addToCart(@Body request: AddToCartRequest): AddToCartResponse

    @GET("user/cart")
    suspend fun getCart(): CartResponse

    @DELETE("user/cart/clear")
    suspend fun clearCart(): ClearCartResponse

    @POST("user/addresses")
    suspend fun addAddress(@Body request: AddAddressRequest): AddAddressResponse

    @GET("user/discovery/searches/products")
    suspend fun searchProducts(
        @Query("query") query: String,
    ): ApiResponse<ProductDto>

    @GET("user/discovery/searches/vendors")
    suspend fun searchVendors(
        @Query("query") query: String
    ): ApiResponse<VendorDto>

    @GET("user/discovery/vendors/nearby")
    suspend fun getNearbyVendors(): NearbyVendorsResponse

    @GET("user/cart/vendor/{vendorId}")
    suspend fun getVendorCart(@Path("vendorId") vendorId: String): VendorCartResponse

    @DELETE("user/cart/item/{itemId}")
    suspend fun deleteCartItem(@Path("itemId") itemId: String): DeleteCartItemResponse

    @POST("user/cart/process")
    suspend fun processCart(@Body request: ProcessCartRequest): ProcessCartResponse

    @DELETE("user/cart/vendor/{vendorId}")
    suspend fun clearVendorCart(@Path("vendorId") vendorId: String): ClearVendorCartResponse

    @PUT("user/cart/item/{itemId}")
    suspend fun updateCartItem(
        @Path("itemId") itemId: String,
        @Body request: UpdateCartItemRequest
    ): DeleteCartItemResponse

    @GET("user/orders")
    suspend fun getOrders(): ApiResponses<PaginatedResponse<Order>>

    @GET("user/orders/{id}")
    suspend fun getOrderById(
        @Path("id") orderId: String
    ): Response<ApiResponses<Order>>

    @GET("user/orders/{id}/track")
    suspend fun getOrderTracking(
        @Path("id") orderId: String
    ): Response<OrderTrackingResponse>

    @GET("user/orders/{id}/eta")
    suspend fun getOrderEta(
        @Path("id") orderId: String
    ): Response<OrderEtaResponse>

    @POST("user/orders/{orderId}/cancel")
    suspend fun cancelOrder(
        @Path("orderId") orderId: String,
        @Body request: Map<String, String>
    ): Response<BaseResponse>

    @POST("user/orders/{orderId}/refund-request")
    suspend fun requestRefund(
        @Path("orderId") orderId: String,
        @Body request: Map<String, String>
    ): Response<BaseResponse>

    @GET("user/orders/{orderId}/refund-status")
    suspend fun getRefundStatus(
        @Path("orderId") orderId: String
    ): Response<RefundStatusResponse>

    @POST("user/reviews")
    suspend fun submitReview(@Body request: ReviewRequest): Response<ReviewResponse>

    @POST("user/discovery/waitlist/join")
    suspend fun joinWaitlist(): WaitlistResponse

    @PUT("user/account/update-profile")
    suspend fun updateProfile(
        @Query("full_name") fullName: String,
        @Query("phone") phone: String,
        @Query("email") email: String
    ): UpdateProfileResponse

    @GET("user/wallet/balance")
    suspend fun getWalletBalance(): WalletBalanceResponse

    // Fund wallet (deposit)
    @POST("user/wallet/deposit")
    suspend fun depositToWallet(@Body request: DepositRequest): DepositResponse

    // Get wallet transactions
    @GET("user/wallet/transactions")
    suspend fun getWalletTransactions(): WalletTransactionsResponse

    @POST("user/account/logout")
    suspend fun logout(): BaseResponse

    @GET("user/vendor/details")
    fun getVendorDetails(): Call<VendorResponse>

    @GET("user/vendor/analytics/summary")
    suspend fun getVendorAnalyticsSummary(): retrofit2.Response<com.shoppitplus.shoppit.utils.VendorAnalyticsResponse>

    @PUT("user/vendor/store/hours")
    suspend fun updateVendorStoreHours(
        @Body request: StoreHoursRequest
    ): StoreHoursResponse

    @GET("user/vendor/orders/statistics/summary")
    fun getOrderStatistics(
        @Query("year") year: Int,
        @Query("month") month: Int
    ): Call<StatsResponse>

    @GET("user/vendor/orders")
    suspend fun getVendorOrders(): OrdersResponse

    @GET("user/vendor/payouts")
    suspend fun getVendorPayouts(): Response<VendorPayoutsResponse>

    @POST("user/vendor/payouts/withdraw")
    suspend fun requestVendorPayout(
        @Body request: VendorPayoutRequest
    ): Response<VendorPayoutRequestResponse>

    @GET("user/vendor/orders/{id}")
    fun getOrderDetails(@Path("id") orderId: String): Call<OrderResponse>

    @GET("user/vendor/orders/{id}/track")
    suspend fun getVendorOrderTracking(
        @Path("id") orderId: String
    ): Response<OrderTrackingResponse>

    @GET("user/vendor/orders/{id}/eta")
    suspend fun getVendorOrderEta(
        @Path("id") orderId: String
    ): Response<OrderEtaResponse>

    @PUT("user/vendor/orders/{id}/status")
    fun updateOrderStatus(
        @Path("id") orderId: String,
        @Query("status") status: String
    ): Call<GenericResponse>

    @GET("user/notifications")
    suspend fun getNotifications(
        @Query("page") page: Int
    ): Response<NotificationResponse>


    @GET("user/notifications/unread-count")
    fun getUnreadNotificationCount(): Call<UnreadCountResponse>

    @GET("user/notifications/{id}")
    fun getNotification(@Path("id") id: String): Call<SingleNotificationResponse>

    @POST("user/notifications/{id}/read")
    fun markNotificationAsRead(@Path("id") id: String): Call<MarkReadResponse>

    @Multipart
    @POST("user/vendor/products")
    suspend fun createProduct(
        @Part("product_category_id") categoryId: RequestBody,
        @Part("name") name: RequestBody,
        @Part("price") price: RequestBody,
        @Part("approximate_delivery_time") deliveryTime: RequestBody,
        @Part("discount_price") discountPrice: RequestBody? = null,
        @Part("description") description: RequestBody? = null,
        @Part("is_active") isActive: RequestBody,
        @Part avatars: List<MultipartBody.Part>
    ): Response<CreateProductResponse>

    @GET("user/vendor/product-categories")
    suspend fun getProductCategories(): ProductCategoryResponse

    @POST("user/vendor/product-categories")
    suspend fun createProductCategory(
        @Query("name") name: String
    ): CreateCategoryResponse

    @GET("user/vendor/products")
    suspend fun getVendorProducts(): Response<VendorProductsResponse>

    @GET("user/vendor/coupons")
    suspend fun getVendorCoupons(): Response<CouponsListResponse>

    @POST("user/vendor/coupons")
    suspend fun createVendorCoupon(@Body params: Map<String, Any>): Response<BaseResponse>

    @PUT("user/vendor/coupons/{id}")
    suspend fun updateVendorCoupon(
        @Path("id") id: String,
        @Body params: Map<String, Any>
    ): Response<BaseResponse>

    @DELETE("user/vendor/coupons/{id}")
    suspend fun deleteVendorCoupon(@Path("id") id: String): Response<BaseResponse>

    @DELETE("user/vendor/products/{id}")
    suspend fun deleteProduct(
        @Path("id") id: String
    ): Response<DeleteProductResponse>

    @POST("user/vendor/products/{id}/duplicate")
    suspend fun duplicateProduct(@Path("id") id: String): Response<CreateProductResponse>

    @Multipart
    @POST("user/vendor/products/{id}")
    suspend fun updateProduct(
        @Path("id") id: String,

        @Part("name") name: RequestBody? = null,
        @Part("description") description: RequestBody? = null,
        @Part("price") price: RequestBody? = null,
        @Part("discount_price") discountPrice: RequestBody? = null,
        @Part("approximate_delivery_time") approximateDeliveryTime: RequestBody? = null,
        @Part("product_category_id") productCategoryId: RequestBody? = null,
        @Part("is_available") isAvailable: RequestBody? = null,
        @Part avatar: List<MultipartBody.Part>? = null
    ): Response<UpdateProductResponse>

    @POST("user/vendor/products/{id}")
    suspend fun toggleProductAvailability(
        @Path("id") id: String,
        @Body request: ToggleAvailabilityRequest
    ): Response<UpdateProductResponse>

    @GET("user/vendor/subscriptions")
    suspend fun getVendorSubscription(): VendorSubscriptionResponse

    // Driver Auth
    @POST("driver/auth/register")
    suspend fun registerDriver(@Body request: DriverRegisterRequest): Response<DriverAuthResponse>

    @POST("driver/auth/login")
    suspend fun loginDriver(@Body request: DriverLoginRequest): Response<DriverAuthResponse>

    // Driver Profile
    @GET("driver/profile")
    suspend fun getDriverProfile(): Response<DriverProfileResponse>

    @PUT("driver/profile")
    suspend fun updateDriverProfile(@Body request: RequestBody): Response<DriverProfileResponse>

    @POST("driver/status")
    suspend fun updateDriverStatus(@Body request: DriverStatusRequest): Response<BaseResponse>

    @POST("driver/fcm-token")
    suspend fun updateDriverFcmToken(@Body request: RequestBody): Response<BaseResponse>

    // Driver Orders
    @GET("driver/orders/available")
    suspend fun getAvailableOrders(
        @Query("latitude") latitude: Double? = null,
        @Query("longitude") longitude: Double? = null,
        @Query("vendor_id") vendorId: String? = null
    ): Response<DriverOrdersResponse>

    @GET("driver/orders/active")
    suspend fun getActiveOrder(): Response<DriverOrderResponse>

    @GET("driver/orders/history")
    suspend fun getOrderHistory(): Response<DriverOrdersResponse>

    @POST("driver/orders/{orderId}/accept")
    suspend fun acceptDriverOrder(@Path("orderId") orderId: String): Response<DriverOrderResponse>

    @POST("driver/orders/{orderId}/reject")
    suspend fun rejectDriverOrder(
        @Path("orderId") orderId: String,
        @Body request: DriverRejectRequest
    ): Response<DriverOrderResponse>

    @POST("driver/orders/{orderId}/pickup")
    suspend fun pickupDriverOrder(@Path("orderId") orderId: String): Response<DriverOrderResponse>

    @POST("driver/orders/{orderId}/out-for-delivery")
    suspend fun startDriverDelivery(@Path("orderId") orderId: String): Response<DriverOrderResponse>

    @POST("driver/orders/{orderId}/deliver")
    suspend fun deliverDriverOrder(
        @Path("orderId") orderId: String,
        @Body request: DriverOtpRequest
    ): Response<DriverOrderResponse>

    @POST("driver/orders/{orderId}/cancel")
    suspend fun cancelDriverOrder(
        @Path("orderId") orderId: String,
        @Body request: DriverRejectRequest
    ): Response<DriverOrderResponse>

    // Driver Earnings & Payouts
    @GET("driver/earnings")
    suspend fun getDriverEarnings(): Response<DriverEarningsResponse>

    @GET("driver/earnings/history")
    suspend fun getDriverEarningsHistory(): Response<DriverEarningsResponse>

    @GET("driver/payouts")
    suspend fun getDriverPayouts(): Response<DriverPayoutResponse>

    @GET("driver/payouts/balance")
    suspend fun getDriverPayoutBalance(): Response<DriverPayoutResponse>

    @POST("driver/payouts/request")
    suspend fun requestDriverPayout(@Body request: DriverPayoutRequest): Response<DriverPayoutResponse>

    // Driver Stats
    @GET("driver/stats")
    suspend fun getDriverStats(): Response<DriverStatsResponse>

    // Driver Location
    @POST("driver/location")
    suspend fun updateDriverLocation(@Body request: DriverLocationUpdateRequest): Response<BaseResponse>

    @POST("driver/location-update")
    suspend fun updateDriverLocationFast(@Body request: DriverLocationUpdateRequest): Response<BaseResponse>

    // Driver Support
    @GET("driver/support/tickets")
    suspend fun getDriverSupportTickets(): Response<DriverSupportResponse>

    @POST("driver/support/tickets")
    suspend fun createDriverSupportTicket(@Body request: DriverSupportRequest): Response<DriverSupportResponse>

    // Driver Navigation
    @POST("driver/navigation/route")
    suspend fun getDriverRoute(@Body request: DriverNavigationRequest): Response<DriverSupportResponse>

    // Driver Vehicles
    @GET("driver/vehicles")
    suspend fun getDriverVehicles(): Response<DriverVehiclesResponse>

    @POST("driver/vehicles")
    suspend fun addDriverVehicle(@Body request: RequestBody): Response<DriverVehiclesResponse>

    @PUT("driver/vehicles/{id}")
    suspend fun updateDriverVehicle(
        @Path("id") id: String,
        @Body request: RequestBody
    ): Response<DriverVehiclesResponse>

    @DELETE("driver/vehicles/{id}")
    suspend fun deleteDriverVehicle(@Path("id") id: String): Response<DriverVehiclesResponse>
}