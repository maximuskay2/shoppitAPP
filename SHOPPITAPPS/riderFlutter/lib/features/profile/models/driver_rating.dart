class DriverReview {
  const DriverReview({
    required this.id,
    required this.rating,
    required this.comment,
    required this.reviewerName,
    required this.reviewerAvatar,
    required this.createdAt,
  });

  final String id;
  final int rating;
  final String comment;
  final String reviewerName;
  final String reviewerAvatar;
  final String createdAt;

  factory DriverReview.fromJson(Map<String, dynamic> json) {
    final user = json["user"] as Map<String, dynamic>? ?? {};
    return DriverReview(
      id: (json["id"] ?? "").toString(),
      rating: (json["rating"] ?? 0) is int
          ? json["rating"] as int
          : int.tryParse((json["rating"] ?? "0").toString()) ?? 0,
      comment: (json["comment"] ?? "").toString(),
      reviewerName: (user["name"] ?? "").toString(),
      reviewerAvatar: (user["avatar"] ?? "").toString(),
      createdAt: (json["created_at"] ?? "").toString(),
    );
  }
}

class DriverRatingSummary {
  const DriverRatingSummary({
    required this.averageRating,
    required this.totalReviews,
    required this.reviews,
  });

  final double averageRating;
  final int totalReviews;
  final List<DriverReview> reviews;

  factory DriverRatingSummary.fromJson(Map<String, dynamic> json) {
    final reviewsJson = json["reviews"] as List<dynamic>? ?? [];
    return DriverRatingSummary(
      averageRating: (json["average_rating"] ?? 0) is num
          ? (json["average_rating"] as num).toDouble()
          : double.tryParse((json["average_rating"] ?? "0").toString()) ?? 0,
      totalReviews: (json["total_reviews"] ?? 0) is int
          ? json["total_reviews"] as int
          : int.tryParse((json["total_reviews"] ?? "0").toString()) ?? 0,
      reviews: reviewsJson
          .whereType<Map<String, dynamic>>()
          .map(DriverReview.fromJson)
          .toList(),
    );
  }
}
