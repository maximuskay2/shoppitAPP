import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/ratings_service.dart";
import "../models/driver_rating.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kGoldColor = Color(0xFFFFC107);

class RatingsScreen extends StatefulWidget {
  const RatingsScreen({super.key});

  @override
  State<RatingsScreen> createState() => _RatingsScreenState();
}

class _RatingsScreenState extends State<RatingsScreen> {
  DriverRatingSummary? _summary;
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadRatings();
  }

  Future<void> _loadRatings() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = RatingsService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchRatings();
      if (!mounted) return;

      setState(() {
        _summary = result.data;
        _error = result.success ? null : result.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load ratings.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      appBar: AppBar(
        backgroundColor: kBackgroundColor,
        elevation: 0,
        centerTitle: true,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: kSurfaceColor,
              shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
            ),
            child: const Icon(Icons.arrow_back, color: kTextDark, size: 20),
          ),
        ),
        title: const Text(
          "Reviews",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
              : SafeArea(
                  child: ListView(
                    padding: const EdgeInsets.all(24),
                    physics: const BouncingScrollPhysics(),
                    children: [
                      // --- 1. Hero Rating Section ---
                      _buildRatingHero(_summary),
                      
                      const SizedBox(height: 32),
                      
                      // --- 2. Reviews List Header ---
                      Row(
                        children: [
                          const Text(
                            "Recent Feedback",
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: kTextDark,
                            ),
                          ),
                          const Spacer(),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: kPrimaryColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              "${_summary?.totalReviews ?? 0} Reviews",
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: kPrimaryColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // --- 3. Reviews List ---
                      if (_summary == null || _summary!.reviews.isEmpty)
                        _buildEmptyState()
                      else
                        ..._summary!.reviews.map((review) => _buildReviewCard(review)),
                        
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
    );
  }

  // --- Hero Section ---
  Widget _buildRatingHero(DriverRatingSummary? summary) {
    final double rating = summary?.averageRating ?? 0.0;
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 32),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2C3E50).withOpacity(0.08),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: kGoldColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.star_rounded, size: 48, color: kGoldColor),
          ),
          const SizedBox(height: 16),
          Text(
            rating.toStringAsFixed(1),
            style: const TextStyle(
              fontSize: 48,
              fontWeight: FontWeight.w900,
              color: kTextDark,
              height: 1,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            "Overall Rating",
            style: TextStyle(
              fontSize: 14,
              color: kTextLight,
              fontWeight: FontWeight.w600,
              letterSpacing: 1,
            ),
          ),
          const SizedBox(height: 16),
          // Star Bar
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(5, (index) {
              return Icon(
                index < rating.round() ? Icons.star_rounded : Icons.star_outline_rounded,
                color: index < rating.round() ? kGoldColor : kTextLight.withOpacity(0.3),
                size: 24,
              );
            }),
          ),
        ],
      ),
    );
  }

  // --- Review Card ---
  Widget _buildReviewCard(DriverReview review) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // Avatar Placeholder
              Container(
                height: 40, width: 40,
                decoration: BoxDecoration(
                  color: kPrimaryColor.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    review.reviewerName.isNotEmpty ? review.reviewerName[0].toUpperCase() : "A",
                    style: const TextStyle(fontWeight: FontWeight.bold, color: kPrimaryColor),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      review.reviewerName.isEmpty ? "Anonymous" : review.reviewerName,
                      style: const TextStyle(fontWeight: FontWeight.bold, color: kTextDark),
                    ),
                    // If you had a date, it would go here
                    // Text("2 days ago", style: TextStyle(fontSize: 10, color: kTextLight)),
                  ],
                ),
              ),
              // Rating Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: kGoldColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Text(
                      review.rating.toString(),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: kTextDark),
                    ),
                    const SizedBox(width: 4),
                    const Icon(Icons.star_rounded, size: 14, color: kGoldColor),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (review.comment.isNotEmpty) ...[
            Text(
              review.comment,
              style: const TextStyle(
                color: kTextDark,
                fontSize: 14,
                height: 1.5,
              ),
            ),
          ] else
            Text(
              "No written feedback.",
              style: TextStyle(
                color: kTextLight.withOpacity(0.6),
                fontSize: 13,
                fontStyle: FontStyle.italic,
              ),
            ),
        ],
      ),
    );
  }

  // --- Empty State ---
  Widget _buildEmptyState() {
    return Container(
      padding: const EdgeInsets.all(40),
      alignment: Alignment.center,
      child: Column(
        children: [
          Icon(Icons.rate_review_outlined, size: 64, color: kTextLight.withOpacity(0.3)),
          const SizedBox(height: 16),
          const Text(
            "No Reviews Yet",
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
          ),
          const SizedBox(height: 8),
          const Text(
            "Complete more trips to start collecting feedback from riders.",
            textAlign: TextAlign.center,
            style: TextStyle(color: kTextLight),
          ),
        ],
      ),
    );
  }
}