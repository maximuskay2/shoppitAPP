/* eslint-disable @typescript-eslint/no-explicit-any */
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { BiArrowBack } from "react-icons/bi";
import { apiUrl } from "../lib/api";

const ForgotPassword = () => {
  const navigate = useNavigate();
  const [step, setStep] = useState(1); // 1: Email, 2: OTP, 3: New Password
  const [isLoading, setIsLoading] = useState(false);

  // Form State
  const [email, setEmail] = useState("");
  const [otp, setOtp] = useState("");
  const [password, setPassword] = useState("");

  // Base API URL
  const API_URL = apiUrl("/api/v1/admin/auth");

  // --- STEP 1: Request OTP ---
  const handleSendEmail = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    try {
      const res = await fetch(`${API_URL}/send-code`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.message || "Failed to send OTP");

      alert(`OTP sent to ${email}`);
      setStep(2); // Move to next step
    } catch (err: any) {
      alert(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  // --- STEP 2: Verify OTP ---
  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    try {
      const res = await fetch(`${API_URL}/verify-code`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, otp }),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.message || "Invalid OTP");

      setStep(3); // Move to next step
    } catch (err: any) {
      alert(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  // --- STEP 3: Reset Password ---
  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    try {
      // Backend requires email, otp, and password to finalize reset
      const res = await fetch(`${API_URL}/reset-password`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, otp, password }),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.message || "Failed to reset password");

      alert("Password reset successfully! Please login.");
      navigate("/"); // Redirect to Login
    } catch (err: any) {
      alert(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100 font-sans px-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 relative">
        {/* Back Button */}
        <button
          onClick={() => navigate("/")}
          className="absolute top-6 left-6 text-gray-500 hover:text-[#1F6728] transition-colors"
        >
          <BiArrowBack size={24} />
        </button>

        <div className="text-center mb-8 mt-4">
          <h1 className="text-3xl font-bold text-gray-800">
            {step === 1 && "Forgot Password?"}
            {step === 2 && "Enter OTP"}
            {step === 3 && "Reset Password"}
          </h1>
          <p className="text-gray-500 text-sm mt-2">
            {step === 1 && "Enter your email to receive a verification code."}
            {step === 2 && `We sent a code to ${email}`}
            {step === 3 && "Create a new secure password."}
          </p>
        </div>

        {/* STEP 1 FORM: EMAIL */}
        {step === 1 && (
          <form onSubmit={handleSendEmail} className="flex flex-col gap-4">
            <input
              type="email"
              placeholder="Enter your email"
              className="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#1F6728] outline-none"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
            <button disabled={isLoading} className="btn-primary">
              {isLoading ? "Sending..." : "Send OTP"}
            </button>
          </form>
        )}

        {/* STEP 2 FORM: OTP */}
        {step === 2 && (
          <form onSubmit={handleVerifyOtp} className="flex flex-col gap-4">
            <input
              type="text"
              placeholder="Enter 6-digit OTP"
              className="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#1F6728] outline-none text-center text-xl tracking-widest"
              value={otp}
              onChange={(e) => setOtp(e.target.value)}
              maxLength={6}
              required
            />
            <button disabled={isLoading} className="btn-primary">
              {isLoading ? "Verifying..." : "Verify OTP"}
            </button>
            <p
              onClick={() => setStep(1)}
              className="text-center text-sm text-gray-500 cursor-pointer hover:text-[#1F6728]"
            >
              Wrong email? Go back
            </p>
          </form>
        )}

        {/* STEP 3 FORM: NEW PASSWORD */}
        {step === 3 && (
          <form onSubmit={handleResetPassword} className="flex flex-col gap-4">
            <input
              type="password"
              placeholder="New Password"
              className="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#1F6728] outline-none"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={6}
            />
            <button disabled={isLoading} className="btn-primary">
              {isLoading ? "Resetting..." : "Set New Password"}
            </button>
          </form>
        )}
      </div>

      {/* Reusable Button Style */}
      <style>{`
        .btn-primary {
          width: 100%;
          background-color: #1F6728;
          color: white;
          font-weight: bold;
          padding: 12px;
          border-radius: 8px;
          transition: background-color 0.3s;
        }
        .btn-primary:hover {
          background-color: #164f1e;
        }
        .btn-primary:disabled {
          background-color: #1F6728;
          opacity: 0.7;
          cursor: not-allowed;
        }
      `}</style>
    </div>
  );
};

export default ForgotPassword;
