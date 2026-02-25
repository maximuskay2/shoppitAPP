package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class MessagingListResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: List<ConversationDto>? = null
)

@Serializable
data class ConversationDto(
    @SerialName("id") val id: String,
    @SerialName("type") val type: String? = null,
    @SerialName("order_id") val orderId: String? = null,
    @SerialName("other") val other: OtherParticipantDto? = null,
    @SerialName("latest_message") val latestMessage: MessageDto? = null,
    @SerialName("updated_at") val updatedAt: String? = null
)

@Serializable
data class OtherParticipantDto(
    @SerialName("id") val id: String,
    @SerialName("name") val name: String,
    @SerialName("email") val email: String? = null
)

@Serializable
data class MessageDto(
    @SerialName("id") val id: String,
    @SerialName("content") val content: String,
    @SerialName("sender_type") val senderType: String? = null,
    @SerialName("sender_id") val senderId: String? = null,
    @SerialName("sender_name") val senderName: String? = null,
    @SerialName("is_mine") val isMine: Boolean = false,
    @SerialName("read_at") val readAt: String? = null,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class MessagesResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: MessagesData? = null
)

@Serializable
data class MessagesData(
    @SerialName("data") val messages: List<MessageDto>,
    @SerialName("meta") val meta: MessagesMeta? = null
)

@Serializable
data class MessagesMeta(
    @SerialName("current_page") val currentPage: Int,
    @SerialName("last_page") val lastPage: Int,
    @SerialName("per_page") val perPage: Int,
    @SerialName("total") val total: Int
)

@Serializable
data class SendMessageResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: MessageDto? = null
)

@Serializable
data class ConversationResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: ConversationDto? = null
)
