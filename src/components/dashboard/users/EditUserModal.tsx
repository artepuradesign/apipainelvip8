
import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Loader2, Calendar, Clock, Wallet } from "lucide-react";
import { getFullApiUrl } from '@/utils/apiHelper';
import type { User } from "@/types/user";
import { format, differenceInDays, parseISO } from "date-fns";
import { ptBR } from "date-fns/locale";
import { getDiscount } from '@/utils/planUtils';

interface Plan {
  id: number;
  name: string;
  slug: string;
  price: number;
  priceFormatted: string;
  discount_percentage?: number;
}

interface EditUserModalProps {
  user: User | null;
  isOpen: boolean;
  onClose: () => void;
  onSave: () => void;
  onUserChange: (user: User) => void;
}

const EditUserModal = ({ user, isOpen, onClose, onSave, onUserChange }: EditUserModalProps) => {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [addPlanBalance, setAddPlanBalance] = useState(false);
  const [selectedPlanPrice, setSelectedPlanPrice] = useState(0);

  useEffect(() => {
    if (isOpen) {
      fetchPlans();
      setAddPlanBalance(false);
      setSelectedPlanPrice(0);
    }
  }, [isOpen]);

  const fetchPlans = async () => {
    setLoadingPlans(true);
    try {
      const response = await fetch(getFullApiUrl('/plans/active'));
      const result = await response.json();
      if (result.success && result.data) {
        setPlans(result.data);
      }
    } catch (error) {
      console.error('Erro ao carregar planos:', error);
    } finally {
      setLoadingPlans(false);
    }
  };

  if (!user) return null;

  const handlePlanChange = (value: string) => {
    const selectedPlan = plans.find(p => p.name === value);
    const discount = selectedPlan?.discount_percentage ?? getDiscount(value);
    const price = selectedPlan?.price || 0;
    setSelectedPlanPrice(price);
    
    if (addPlanBalance && price > 0) {
      onUserChange({ ...user, plan: value, planDiscount: discount, planBalance: (user.planBalance || 0) + price });
    } else {
      onUserChange({ ...user, plan: value, planDiscount: discount });
    }
  };

  const handleToggleAddPlanBalance = (checked: boolean) => {
    setAddPlanBalance(checked);
    if (checked && selectedPlanPrice > 0) {
      onUserChange({ ...user, planBalance: (user.planBalance || 0) + selectedPlanPrice });
    } else if (!checked && selectedPlanPrice > 0) {
      // Reverter o saldo adicionado
      onUserChange({ ...user, planBalance: Math.max(0, (user.planBalance || 0) - selectedPlanPrice) });
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="w-[95vw] max-w-lg p-4 sm:p-6">
        <DialogHeader className="pb-2">
          <DialogTitle className="text-base">Editar Usuário</DialogTitle>
        </DialogHeader>
        <div className="space-y-3 max-h-[70vh] overflow-y-auto pr-1">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <Label htmlFor="edit-name" className="text-xs">Nome Completo</Label>
              <Input
                id="edit-name"
                className="h-8 text-sm"
                value={user.name}
                onChange={(e) => onUserChange({ ...user, name: e.target.value })}
              />
            </div>
            <div>
              <Label htmlFor="edit-email" className="text-xs">E-mail</Label>
              <Input
                id="edit-email"
                className="h-8 text-sm"
                value={user.email}
                onChange={(e) => onUserChange({ ...user, email: e.target.value })}
              />
            </div>
            <div>
              <Label htmlFor="edit-balance" className="text-xs">Saldo da Carteira</Label>
              <Input
                id="edit-balance"
                type="number"
                step="0.01"
                className="h-8 text-sm"
                value={user.balance}
                onChange={(e) => onUserChange({ ...user, balance: parseFloat(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-balance" className="text-xs">Saldo do Plano</Label>
              <Input
                id="edit-plan-balance"
                type="number"
                step="0.01"
                className="h-8 text-sm"
                value={user.planBalance || 0}
                onChange={(e) => onUserChange({ ...user, planBalance: parseFloat(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan" className="text-xs">Plano</Label>
              {loadingPlans ? (
                <div className="flex items-center gap-2 h-8 px-3 border rounded-md">
                  <Loader2 className="h-3 w-3 animate-spin" />
                  <span className="text-xs text-muted-foreground">Carregando...</span>
                </div>
              ) : (
                <Select
                  value={user.plan}
                  onValueChange={handlePlanChange}
                >
                  <SelectTrigger id="edit-plan" className="h-8 text-sm">
                    <SelectValue placeholder="Selecione um plano" />
                  </SelectTrigger>
                  <SelectContent>
                    {plans.map((plan) => (
                      <SelectItem key={plan.id} value={plan.name}>
                        {plan.name} - {plan.priceFormatted}
                      </SelectItem>
                    ))}
                    {plans.length === 0 && (
                      <SelectItem value={user.plan} disabled={false}>
                        {user.plan}
                      </SelectItem>
                    )}
                  </SelectContent>
                </Select>
              )}
              {/* Opção para adicionar saldo do plano */}
              <div className="sm:col-span-2 flex items-center justify-between gap-3 p-3 rounded-lg border border-border bg-muted/30">
                <div className="flex items-center gap-2">
                  <Wallet className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                  <div>
                    <Label className="text-xs font-medium">Adicionar valor do plano ao saldo</Label>
                    <p className="text-[10px] text-muted-foreground">
                      {selectedPlanPrice > 0 
                        ? `Será adicionado R$ ${selectedPlanPrice.toFixed(2)} ao saldo do plano`
                        : 'Selecione um plano diferente para habilitar'}
                    </p>
                  </div>
                </div>
                <Switch 
                  checked={addPlanBalance}
                  onCheckedChange={handleToggleAddPlanBalance}
                  disabled={selectedPlanPrice <= 0}
                />
              </div>
            </div>
            <div>
              <Label htmlFor="edit-plan-discount" className="text-xs text-emerald-600 dark:text-emerald-400">
                Desconto do Plano (%)
              </Label>
              <Input
                id="edit-plan-discount"
                type="number"
                min="0"
                max="100"
                className="h-8 text-sm"
                value={user.planDiscount || 0}
                onChange={(e) => onUserChange({ ...user, planDiscount: parseInt(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-start" className="text-xs flex items-center gap-1">
                <Calendar className="h-3 w-3" /> Início do Plano
              </Label>
              <Input
                id="edit-plan-start"
                className="h-8 text-sm bg-muted cursor-default"
                value={user.planStartDate ? format(parseISO(user.planStartDate), 'dd/MM/yyyy', { locale: ptBR }) : 'Não definido'}
                readOnly
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-end" className="text-xs flex items-center gap-1">
                <Calendar className="h-3 w-3" /> Término do Plano
              </Label>
              <Input
                id="edit-plan-end"
                className="h-8 text-sm bg-muted cursor-default"
                value={user.planEndDate ? format(parseISO(user.planEndDate), 'dd/MM/yyyy', { locale: ptBR }) : 'Não definido'}
                readOnly
              />
            </div>
            <div>
              <Label htmlFor="edit-days-remaining" className="text-xs flex items-center gap-1">
                <Clock className="h-3 w-3" /> Dias Restantes
              </Label>
              <Input
                id="edit-days-remaining"
                className="h-8 text-sm bg-muted cursor-default"
                value={user.planEndDate ? Math.max(0, differenceInDays(parseISO(user.planEndDate), new Date())) + ' dias' : 'N/A'}
                readOnly
              />
            </div>
            <div>
              <Label htmlFor="edit-notes" className="text-xs">Observações</Label>
              <Input
                id="edit-notes"
                className="h-8 text-sm"
                value={user.notes || ''}
                onChange={(e) => onUserChange({ ...user, notes: e.target.value })}
                placeholder="Observação para o usuário..."
              />
            </div>
          </div>
          <div className="flex gap-2 pt-2">
            <Button size="sm" onClick={onSave}>Salvar</Button>
            <Button size="sm" variant="outline" onClick={onClose}>Cancelar</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default EditUserModal;
