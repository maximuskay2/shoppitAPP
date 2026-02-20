package com.shoppitplus.shoppit.wallet

import android.graphics.Bitmap
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.databinding.FragmentPaystackWebviewBinding
import com.shoppitplus.shoppit.ui.TopBanner

class fragment_paystack_webview : Fragment() {
    private var _binding: FragmentPaystackWebviewBinding? = null
    private val binding get() = _binding!!
    private var webViewRef: WebView? = null  // Safe reference for cleanup

    companion object {
        private const val ARG_URL = "url"

        fun newInstance(url: String): fragment_paystack_webview {
            return fragment_paystack_webview().apply {
                arguments = Bundle().apply {
                    putString(ARG_URL, url)
                }
            }
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentPaystackWebviewBinding.inflate(inflater, container, false)

        // Back button
        binding.backButton.setOnClickListener {
            findNavController().popBackStack()
        }
        return binding.root

    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val url = arguments?.getString(ARG_URL) ?: run {
            TopBanner.showError(requireActivity(), "Payment URL missing")
            findNavController().popBackStack()
            return
        }

        setupWebView(url)
        setupBackButton()
    }

    private fun setupWebView(url: String) {
        binding.webView.apply {
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.loadWithOverviewMode = true
            settings.useWideViewPort = true

            // Progress bar
            webChromeClient = object : WebChromeClient() {
                override fun onProgressChanged(view: WebView?, newProgress: Int) {
                    binding.progressBar.progress = newProgress
                    binding.progressBar.visibility =
                        if (newProgress == 100) View.GONE else View.VISIBLE
                }
            }

            webViewClient = object : WebViewClient() {
                override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                    binding.progressBar.visibility = View.VISIBLE
                }

                override fun onPageFinished(view: WebView?, url: String?) {
                    binding.progressBar.visibility = View.GONE
                }

                override fun shouldOverrideUrlLoading(view: WebView?, url: String?): Boolean {
                    if (url == null) return false

                    // Detect Paystack callback or close
                    if (url.contains("paystack.co/close") || url.contains("payment/callback")) {
                        handlePaymentCallback(url)
                        return true  // Prevent loading the callback page
                    }

                    return false
                }
            }

            loadUrl(url)
        }

        // Keep reference for safe cleanup
        webViewRef = binding.webView
    }

    private fun setupBackButton() {
        binding.backButton.setOnClickListener {
            if (binding.webView.canGoBack()) {
                binding.webView.goBack()
            } else {
                findNavController().popBackStack()
            }
        }
    }

    private fun handlePaymentCallback(callbackUrl: String) {
        // Extract reference if needed
        val uri = Uri.parse(callbackUrl)
        val reference = uri.getQueryParameter("reference")
        val status = uri.getQueryParameter("status") // sometimes present

        // Close WebView
        findNavController().popBackStack()

        // Show success message
        val message = when {
            status == "success" || reference != null -> "Payment completed successfully!"
            callbackUrl.contains("close") -> "Payment cancelled"
            else -> "Payment completed"
        }

        TopBanner.showSuccess(requireActivity(), message)

        // Optional: verify with backend using reference
        // verifyPaymentOnServer(reference)
    }

    override fun onDestroyView() {
        // Properly clean up WebView to prevent memory leaks
        webViewRef?.let { webView ->
            webView.stopLoading()
            webView.settings.javaScriptEnabled = false
            webView.clearHistory()
            webView.clearCache(true)
            webView.destroy()
        }
        webViewRef = null

        _binding = null
        super.onDestroyView()
    }
}