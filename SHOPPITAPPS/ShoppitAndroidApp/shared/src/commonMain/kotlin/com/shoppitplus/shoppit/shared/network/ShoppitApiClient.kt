package com.shoppitplus.shoppit.shared.network

import com.shoppitplus.shoppit.shared.models.*
import io.ktor.client.*
import io.ktor.client.call.*
import io.ktor.client.plugins.*
import io.ktor.client.plugins.contentnegotiation.*
import io.ktor.client.plugins.logging.*
import io.ktor.client.request.*
import io.ktor.client.request.forms.*
import io.ktor.client.statement.*
import io.ktor.http.*
import io.ktor.serialization.kotlinx.json.*
import kotlinx.serialization.json.Json

class ShoppitApiClient {
    private val client = HttpClient {
        install(ContentNegotiation) {
            json(Json {
                ignoreUnknownKeys = true
                prettyPrint = true
                isLenient = true
            })
        }

        install(Logging) {
            logger = Logger.DEFAULT
            level = LogLevel.ALL
        }

        defaultRequest {
            url("https://laravelapi-production-1ea4.up.railway.app/api/v1/")
        }
    }

    private fun HttpRequestBuilder.authorized(token: String) {
        header(HttpHeaders.Authorization, "Bearer $token")
    }

    // --- WRAPPER FOR ERROR HANDLING ---
    private suspend inline fun <reified T> safeRequest(block: () -> HttpResponse): T {
        return try {
            val response = block()
            if (response.status.isSuccess()) {
                response.body()
            } else {
                throw Exception("API Error: ${response.status.description}")
            }
        } catch (e: Exception) {
            throw e
        }
    }

    suspend fun login(request: LoginRequest): LoginResponse = safeRequest {
        client.post("auth/login") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun register(request: RegistrationRequest): RegistrationResponse {
        val response = client.post("auth/register") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }
        return if (response.status.isSuccess()) {
            response.body()
        } else {
            val errorMessage = try {
                val bodyText = response.bodyAsText()
                Json.decodeFromString<ApiErrorResponse>(bodyText).message
            } catch (e: Exception) {
                response.status.description
            }
            throw Exception(errorMessage)
        }
    }

    suspend fun validateToken(token: String): BaseResponse = safeRequest {
        client.get("auth/validate-token") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun logout(token: String): BaseResponse = safeRequest {
        client.post("user/account/logout") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun sendResetCode(request: ResetCodeRequest): BaseResponse = safeRequest {
        client.post("auth/send-code") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun verifyResetCode(request: VerifyCodeRequest): BaseResponse = safeRequest {
        client.post("auth/verify-code") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun resetPassword(request: ResetPasswordRequest): BaseResponse = safeRequest {
        client.post("auth/reset-password") {
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun getNearbyVendors(): NearbyVendorsResponse = safeRequest {
        client.get("user/discovery/vendors/nearby") {
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getNewProducts(): ProductResponse = safeRequest {
        client.get("user/discovery/products/nearby") {
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getUserAccount(token: String): UserResponse = safeRequest {
        client.get("user/account") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun updateProfile(token: String, request: UpdateProfileRequest): UpdateProfileResponse = safeRequest {
        client.put("user/account/update-profile") {
            authorized(token)
            parameter("full_name", request.fullName)
            parameter("phone", request.phone)
            parameter("email", request.email)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun joinWaitlist(token: String): BaseResponse = safeRequest {
        client.post("user/discovery/waitlist/join") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun addToCart(token: String, request: AddToCartRequest): AddToCartResponse = safeRequest {
        client.post("user/cart/add") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun getCart(token: String): CartResponse = safeRequest {
        client.get("user/cart") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun clearCart(token: String): BaseResponse = safeRequest {
        client.delete("user/cart/clear") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun clearVendorCart(token: String, vendorId: String): BaseResponse = safeRequest {
        client.delete("user/cart/vendor/$vendorId") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getVendorCart(token: String, vendorId: String): VendorCartResponse = safeRequest {
        client.get("user/cart/vendor/$vendorId") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun updateCartItem(token: String, itemId: String, request: UpdateCartItemRequest): BaseResponse = safeRequest {
        client.put("user/cart/item/$itemId") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun deleteCartItem(token: String, itemId: String): BaseResponse = safeRequest {
        client.delete("user/cart/item/$itemId") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun processCart(token: String, request: ProcessCartRequest): ProcessCartResponse = safeRequest {
        client.post("user/cart/process") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun checkDeliveryZone(token: String, lat: Double, long: Double): DeliveryZoneCheckResponse = safeRequest {
        client.get("delivery-zones/check") {
            authorized(token)
            parameter("latitude", lat)
            parameter("longitude", long)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun searchProducts(query: String): ApiResponse<ProductDto> = safeRequest {
        client.get("user/discovery/searches/products") {
            parameter("query", query)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun searchVendors(query: String): ApiResponse<VendorDto> = safeRequest {
        client.get("user/discovery/searches/vendors") {
            parameter("query", query)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getOrders(token: String): OrdersResponse = safeRequest {
        client.get("user/orders") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    // --- VENDOR ENDPOINTS ---
    suspend fun getVendorDetails(token: String): VendorResponse = safeRequest {
        client.get("user/vendor/details") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun updateVendorStoreHours(token: String, request: StoreHoursRequest): StoreHoursResponse = safeRequest {
        client.put("user/vendor/store/hours") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun getUnreadNotificationCount(token: String): UnreadCountResponse = safeRequest {
        client.get("user/notifications/unread-count") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getWalletBalance(token: String): WalletBalanceResponse = safeRequest {
        client.get("user/wallet/balance") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getWalletTransactions(token: String): WalletTransactionsResponse = safeRequest {
        client.get("user/wallet/transactions") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun depositToWallet(token: String, request: DepositRequest): DepositResponse = safeRequest {
        client.post("user/wallet/deposit") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun getOrderStatistics(token: String, year: Int, month: Int): StatsResponse = safeRequest {
        client.get("user/vendor/orders/statistics/summary") {
            authorized(token)
            parameter("year", year)
            parameter("month", month)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getVendorAnalyticsSummary(token: String): VendorAnalyticsResponse = safeRequest {
        client.get("user/vendor/analytics/summary") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getVendorProducts(token: String): VendorProductsResponse = safeRequest {
        client.get("user/vendor/products") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun duplicateProduct(token: String, productId: String): CreateProductResponse = safeRequest {
        client.post("user/vendor/products/$productId/duplicate") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun toggleProductAvailability(token: String, productId: String, request: ToggleAvailabilityRequest): CreateProductResponse = safeRequest {
        client.post("user/vendor/products/$productId") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun deleteProduct(token: String, productId: String): DeleteProductResponse = safeRequest {
        client.delete("user/vendor/products/$productId") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getProductCategories(token: String): ProductCategoryResponse = safeRequest {
        client.get("user/vendor/product-categories") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun createProductCategory(token: String, name: String): CreateCategoryResponse = safeRequest {
        client.post("user/vendor/product-categories") {
            authorized(token)
            parameter("name", name)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun createProduct(
        token: String,
        categoryId: String,
        name: String,
        price: String,
        deliveryTime: String,
        discountPrice: String?,
        description: String?,
        isActive: String,
        imageDatas: List<ByteArray>
    ): CreateProductResponse = safeRequest {
        client.submitFormWithBinaryData(
            url = "user/vendor/products",
            formData = formData {
                append("product_category_id", categoryId)
                append("name", name)
                append("price", price)
                append("approximate_delivery_time", deliveryTime)
                discountPrice?.let { append("discount_price", it) }
                description?.let { append("description", it) }
                append("is_active", isActive)

                imageDatas.forEachIndexed { index, bytes ->
                    append("avatar[$index]", bytes, Headers.build {
                        append(HttpHeaders.ContentType, "image/jpeg")
                        append(HttpHeaders.ContentDisposition, "filename=\"image_$index.jpg\"")
                    })
                }
            }
        ) {
            authorized(token)
        }
    }

    suspend fun getVendorOrders(token: String): OrdersResponse = safeRequest {
        client.get("user/vendor/orders") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getOrderDetails(token: String, orderId: String): OrderResponse = safeRequest {
        client.get("user/vendor/orders/$orderId") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun updateOrderStatus(token: String, orderId: String, status: String): BaseResponse = safeRequest {
        client.put("user/vendor/orders/$orderId/status") {
            authorized(token)
            parameter("status", status)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getVendorPayouts(token: String): VendorPayoutsResponse = safeRequest {
        client.get("user/vendor/payouts") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun requestVendorPayout(token: String, request: VendorPayoutRequest): VendorPayoutRequestResponse = safeRequest {
        client.post("user/vendor/payouts/withdraw") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(request)
        }
    }

    suspend fun getUnifiedNotifications(token: String, page: Int = 1): NotificationResponse = safeRequest {
        client.get("notifications/unified") {
            authorized(token)
            parameter("page", page)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun markUnifiedNotificationRead(token: String, id: String): MarkReadResponse = safeRequest {
        client.post("notifications/unified/$id/read") {
            authorized(token)
            contentType(ContentType.Application.Json)
        }
    }

    // --- CONSUMER MESSAGING ---
    suspend fun getConsumerConversations(token: String, orderId: String? = null): MessagingListResponse = safeRequest {
        client.get("user/messaging") {
            authorized(token)
            orderId?.let { parameter("order_id", it) }
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getOrCreateConsumerConversationWithDriver(token: String, orderId: String): ConversationResponse = safeRequest {
        val body = mapOf("order_id" to orderId)
        client.post("user/messaging/conversations/driver") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(body)
        }
    }

    suspend fun getConsumerMessages(token: String, conversationId: String, page: Int = 1): MessagesResponse = safeRequest {
        client.get("user/messaging/conversations/$conversationId/messages") {
            authorized(token)
            parameter("page", page)
            parameter("per_page", 50)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun sendConsumerMessage(token: String, conversationId: String, content: String): SendMessageResponse = safeRequest {
        client.post("user/messaging/conversations/$conversationId/messages") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(mapOf("content" to content))
        }
    }

    // --- VENDOR MESSAGING ---
    suspend fun getVendorConversations(token: String, orderId: String? = null): MessagingListResponse = safeRequest {
        client.get("user/vendor/messaging") {
            authorized(token)
            orderId?.let { parameter("order_id", it) }
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun getOrCreateVendorConversationWithDriver(token: String, orderId: String): ConversationResponse = safeRequest {
        val body = mapOf("order_id" to orderId)
        client.post("user/vendor/messaging/conversations/driver") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(body)
        }
    }

    suspend fun getVendorMessages(token: String, conversationId: String, page: Int = 1): MessagesResponse = safeRequest {
        client.get("user/vendor/messaging/conversations/$conversationId/messages") {
            authorized(token)
            parameter("page", page)
            parameter("per_page", 50)
            contentType(ContentType.Application.Json)
        }
    }

    suspend fun sendVendorMessage(token: String, conversationId: String, content: String): SendMessageResponse = safeRequest {
        client.post("user/vendor/messaging/conversations/$conversationId/messages") {
            authorized(token)
            contentType(ContentType.Application.Json)
            setBody(mapOf("content" to content))
        }
    }
}
